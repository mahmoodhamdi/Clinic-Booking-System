<?php

namespace App\Notifications;

use App\Models\Appointment;
use App\Models\ClinicSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentConfirmed extends Notification
{
    use Queueable;

    public function __construct(
        public Appointment $appointment
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($this->shouldEmail($notifiable)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    private function shouldEmail(object $notifiable): bool
    {
        if (! config('clinic.notifications.email_enabled', false)) {
            return false;
        }

        if (empty($notifiable->email)) {
            return false;
        }

        // No FROM address means SMTP won't send. Skip silently rather than
        // crash the queue worker on every confirmation.
        $from = config('mail.from.address');

        return ! empty($from);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'appointment_confirmed',
            'title' => 'تأكيد الموعد',
            'message' => sprintf(
                'تم تأكيد موعدك يوم %s الساعة %s',
                $this->appointment->appointment_date->format('Y-m-d'),
                $this->appointment->formatted_time
            ),
            'appointment_id' => $this->appointment->id,
            'appointment_date' => $this->appointment->appointment_date->format('Y-m-d'),
            'appointment_time' => $this->appointment->formatted_time,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $clinic = ClinicSetting::getInstance();
        $date = $this->appointment->appointment_date->format('Y-m-d');
        $time = $this->appointment->formatted_time;

        return (new MailMessage)
            ->subject(__('mail.appointment_confirmed.subject', ['date' => $date]))
            ->greeting(__('mail.appointment_confirmed.greeting', ['name' => $notifiable->name]))
            ->line(__('mail.appointment_confirmed.intro', [
                'clinic' => $clinic->clinic_name,
                'doctor' => $clinic->doctor_name,
            ]))
            ->line(__('mail.appointment_confirmed.date_line', ['date' => $date]))
            ->line(__('mail.appointment_confirmed.time_line', ['time' => $time]))
            ->line(__('mail.appointment_confirmed.contact_line', [
                'phone' => $clinic->phone ?: '-',
            ]))
            ->salutation(__('mail.appointment_confirmed.salutation'));
    }
}
