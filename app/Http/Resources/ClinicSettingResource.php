<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClinicSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'clinic_name' => $this->clinic_name,
            'doctor_name' => $this->doctor_name,
            'specialization' => $this->specialization,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'logo_url' => $this->logo_url,
            'slot_duration' => $this->slot_duration,
            'max_patients_per_slot' => $this->max_patients_per_slot,
            'advance_booking_days' => $this->advance_booking_days,
            'cancellation_hours' => $this->cancellation_hours,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
