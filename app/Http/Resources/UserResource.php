<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role?->value,
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'gender' => $this->gender?->value,
            'address' => $this->address,
            'avatar' => $this->avatar_url,
            'is_active' => $this->is_active,
            'phone_verified_at' => $this->phone_verified_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
