<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AppointmentReminder extends Notification
{
    use Queueable;

    public function __construct(
        public Appointment $appointment
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'appointment_reminder',
            'title' => 'تذكير بالموعد',
            'message' => sprintf(
                'تذكير: لديك موعد غداً يوم %s الساعة %s',
                $this->appointment->appointment_date->format('Y-m-d'),
                $this->appointment->formatted_time
            ),
            'appointment_id' => $this->appointment->id,
            'appointment_date' => $this->appointment->appointment_date->format('Y-m-d'),
            'appointment_time' => $this->appointment->formatted_time,
        ];
    }
}
