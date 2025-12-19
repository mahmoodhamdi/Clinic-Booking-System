<?php

namespace App\Http\Resources;

use App\Models\ClinicSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $settings = ClinicSetting::getInstance();

        return [
            'id' => $this->id,
            'day_of_week' => $this->day_of_week->value,
            'day_name' => $this->day_name,
            'day_name_en' => $this->day_name_en,
            'start_time' => $this->formatted_start_time,
            'end_time' => $this->formatted_end_time,
            'is_active' => $this->is_active,
            'break_start' => $this->formatted_break_start,
            'break_end' => $this->formatted_break_end,
            'has_break' => $this->hasBreak(),
            'slots_count' => $this->getSlotsCount($settings->slot_duration),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
