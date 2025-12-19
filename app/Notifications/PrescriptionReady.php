<?php

namespace App\Notifications;

use App\Models\Prescription;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PrescriptionReady extends Notification
{
    use Queueable;

    public function __construct(
        public Prescription $prescription
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'prescription_ready',
            'title' => 'وصفة طبية جديدة',
            'message' => sprintf(
                'تم إصدار وصفة طبية جديدة رقم %s',
                $this->prescription->prescription_number
            ),
            'prescription_id' => $this->prescription->id,
            'prescription_number' => $this->prescription->prescription_number,
            'medical_record_id' => $this->prescription->medical_record_id,
        ];
    }
}
