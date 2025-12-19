<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrescriptionItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'prescription_id' => $this->prescription_id,
            'medication_name' => $this->medication_name,
            'dosage' => $this->dosage,
            'frequency' => $this->frequency,
            'duration' => $this->duration,
            'instructions' => $this->instructions,
            'quantity' => $this->quantity,
            'full_dosage_text' => $this->full_dosage_text,
            'full_description' => $this->full_description,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
