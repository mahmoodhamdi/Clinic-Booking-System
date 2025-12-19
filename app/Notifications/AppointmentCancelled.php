<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AppointmentCancelled extends Notification
{
    use Queueable;

    public function __construct(
        public Appointment $appointment,
        public string $cancelledBy,
        public ?string $reason = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $message = sprintf(
            'تم إلغاء موعدك يوم %s الساعة %s',
            $this->appointment->appointment_date->format('Y-m-d'),
            $this->appointment->formatted_time
        );

        if ($this->reason) {
            $message .= '. السبب: ' . $this->reason;
        }

        return [
            'type' => 'appointment_cancelled',
            'title' => 'إلغاء الموعد',
            'message' => $message,
            'appointment_id' => $this->appointment->id,
            'appointment_date' => $this->appointment->appointment_date->format('Y-m-d'),
            'appointment_time' => $this->appointment->formatted_time,
            'cancelled_by' => $this->cancelledBy,
            'reason' => $this->reason,
        ];
    }
}
