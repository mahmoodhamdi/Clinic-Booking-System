<?php

namespace App\Http\Resources;

use App\Enums\AppointmentStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class AppointmentCollection extends ResourceCollection
{
    public $collects = AppointmentResource::class;

    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'summary' => $this->getSummary(),
        ];
    }

    protected function getSummary(): array
    {
        $total = $this->collection->count();
        $byStatus = [];

        foreach (AppointmentStatus::cases() as $status) {
            $byStatus[$status->value] = $this->collection
                ->where('status.value', $status->value)
                ->count();
        }

        return [
            'total' => $total,
            'by_status' => $byStatus,
        ];
    }
}
