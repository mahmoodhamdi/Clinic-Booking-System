<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient' => [
                'id' => $this->patient->id,
                'name' => $this->patient->name,
                'phone' => $this->patient->phone,
                'avatar_url' => $this->patient->avatar_url,
            ],
            'date' => $this->formatted_date,
            'time' => $this->formatted_time,
            'datetime' => $this->datetime->toIso8601String(),
            'day_name' => $this->day_name,
            'day_name_en' => $this->day_name_en,
            'status' => $this->status->value,
            'status_label' => $this->status_label,
            'status_label_en' => $this->status_label_en,
            'status_color' => $this->status->color(),
            'notes' => $this->notes,
            'admin_notes' => $this->admin_notes,
            'can_cancel' => $this->can_cancel,
            'is_upcoming' => $this->is_upcoming,
            'is_today' => $this->is_today,
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'cancellation_reason' => $this->cancellation_reason,
            'cancelled_by' => $this->cancelled_by?->value,
            'cancelled_by_label' => $this->cancelled_by?->labelAr(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
