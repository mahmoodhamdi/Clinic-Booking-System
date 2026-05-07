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
            'tagline' => $this->tagline,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'logo' => $this->logo,
            'logo_url' => $this->logo_url,
            'hero_image' => $this->hero_image,
            'hero_image_url' => $this->hero_image_url,
            'services' => $this->services ?? [],
            'about_text' => $this->about_text,
            'slot_duration' => $this->slot_duration,
            'max_patients_per_slot' => $this->max_patients_per_slot,
            'advance_booking_days' => $this->advance_booking_days,
            'cancellation_hours' => $this->cancellation_hours,
            'setup_completed_at' => $this->setup_completed_at?->toIso8601String(),
            'is_setup_complete' => $this->setup_completed_at !== null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
