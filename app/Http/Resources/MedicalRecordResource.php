<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedicalRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'appointment_id' => $this->appointment_id,
            'patient_id' => $this->patient_id,
            'diagnosis' => $this->diagnosis,
            'symptoms' => $this->symptoms,
            'examination_notes' => $this->examination_notes,
            'treatment_plan' => $this->treatment_plan,
            'follow_up_date' => $this->follow_up_date?->format('Y-m-d'),
            'follow_up_notes' => $this->follow_up_notes,
            'has_follow_up' => $this->has_follow_up,
            'vital_signs' => $this->vital_signs,
            'bmi' => $this->bmi,
            'prescriptions_count' => $this->prescriptions_count,
            'attachments_count' => $this->attachments_count,
            'appointment' => new AppointmentResource($this->whenLoaded('appointment')),
            'patient' => new UserResource($this->whenLoaded('patient')),
            'prescriptions' => PrescriptionResource::collection($this->whenLoaded('prescriptions')),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
