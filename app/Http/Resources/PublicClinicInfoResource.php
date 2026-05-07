<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Public-facing subset of ClinicSetting. Do NOT include operational fields
// (slot_duration, advance_booking_days, etc.) — they're admin/policy data.
// Only the marketing/contact info that a visitor needs to decide to book.
class PublicClinicInfoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'clinic_name' => $this->clinic_name,
            'doctor_name' => $this->doctor_name,
            'specialization' => $this->specialization,
            'tagline' => $this->tagline,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'logo_url' => $this->logo_url,
            'hero_image_url' => $this->hero_image_url,
            'services' => $this->services ?? [],
            'about_text' => $this->about_text,
        ];
    }
}
