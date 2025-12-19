<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'appointment_id' => $this->appointment_id,
            'amount' => (float) $this->amount,
            'discount' => (float) $this->discount,
            'total' => (float) $this->total,
            'formatted_amount' => $this->formatted_amount,
            'formatted_discount' => $this->formatted_discount,
            'formatted_total' => $this->formatted_total,
            'method' => $this->method->value,
            'method_label' => $this->method_label,
            'status' => $this->status->value,
            'status_label' => $this->status_label,
            'transaction_id' => $this->transaction_id,
            'notes' => $this->notes,
            'has_discount' => $this->has_discount,
            'discount_percentage' => $this->discount_percentage,
            'paid_at' => $this->paid_at?->format('Y-m-d H:i:s'),
            'refunded_at' => $this->refunded_at?->format('Y-m-d H:i:s'),
            'appointment' => new AppointmentResource($this->whenLoaded('appointment')),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
