<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientResource extends JsonResource
{
    /**
     * Statistics to include (from PatientStatisticsService).
     */
    protected ?array $statistics = null;

    /**
     * Set statistics from external source.
     */
    public function setStatistics(?array $statistics): self
    {
        $this->statistics = $statistics;

        return $this;
    }

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
            'statistics' => $this->when($this->statistics !== null, fn () => [
                'total_appointments' => $this->statistics['total_appointments'] ?? 0,
                'completed_appointments' => $this->statistics['completed_appointments'] ?? 0,
                'cancelled_appointments' => $this->statistics['cancelled_appointments'] ?? 0,
                'no_shows' => $this->statistics['no_show_count'] ?? 0,
                'upcoming_appointments' => $this->statistics['upcoming_appointments'] ?? 0,
                'last_visit' => $this->statistics['last_visit'] ?? null,
            ]),
        ];
    }
}
