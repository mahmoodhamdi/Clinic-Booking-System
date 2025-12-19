<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrescriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'medical_record_id' => $this->medical_record_id,
            'prescription_number' => $this->prescription_number,
            'notes' => $this->notes,
            'valid_until' => $this->valid_until?->format('Y-m-d'),
            'is_valid' => $this->is_valid,
            'is_expired' => $this->is_expired,
            'is_dispensed' => $this->is_dispensed,
            'dispensed_at' => $this->dispensed_at?->format('Y-m-d H:i:s'),
            'items_count' => $this->items_count,
            'medical_record' => new MedicalRecordResource($this->whenLoaded('medicalRecord')),
            'items' => PrescriptionItemResource::collection($this->whenLoaded('items')),
            'patient' => new UserResource($this->whenLoaded('patient')),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
