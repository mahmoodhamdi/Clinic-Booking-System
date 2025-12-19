<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'blood_type' => $this->blood_type?->value,
            'blood_type_label' => $this->blood_type_label,
            'emergency_contact' => [
                'name' => $this->emergency_contact_name,
                'phone' => $this->emergency_contact_phone,
            ],
            'has_emergency_contact' => $this->has_emergency_contact,
            'allergies' => $this->allergies ?? [],
            'allergies_list' => $this->allergies_list,
            'chronic_diseases' => $this->chronic_diseases ?? [],
            'chronic_diseases_list' => $this->chronic_diseases_list,
            'current_medications' => $this->current_medications ?? [],
            'current_medications_list' => $this->current_medications_list,
            'medical_notes' => $this->medical_notes,
            'insurance' => [
                'provider' => $this->insurance_provider,
                'number' => $this->insurance_number,
            ],
            'has_insurance' => $this->has_insurance,
            'is_complete' => $this->is_complete,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
