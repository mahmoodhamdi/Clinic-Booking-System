<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'age' => $this->age,
            'gender' => $this->gender?->value,
            'gender_label' => $this->gender?->labelAr(),
            'address' => $this->address,
            'avatar_url' => $this->avatar_url,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at->toIso8601String(),
            'profile' => $this->whenLoaded('profile', function () {
                return $this->profile ? new PatientProfileResource($this->profile) : null;
            }),
            'has_profile' => $this->profile !== null,
            'statistics' => [
                'total_appointments' => $this->total_appointments,
                'completed_appointments' => $this->completed_appointments_count,
                'cancelled_appointments' => $this->cancelled_appointments_count,
                'no_shows' => $this->no_show_count,
                'upcoming_appointments' => $this->upcoming_appointments_count,
                'last_visit' => $this->last_visit?->toDateString(),
            ],
        ];
    }
}
