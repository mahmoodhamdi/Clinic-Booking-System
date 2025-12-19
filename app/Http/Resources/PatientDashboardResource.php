<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientDashboardResource extends JsonResource
{
    protected $upcomingAppointments;

    public function __construct($resource, $upcomingAppointments = null)
    {
        parent::__construct($resource);
        $this->upcomingAppointments = $upcomingAppointments;
    }

    public function toArray(Request $request): array
    {
        $nextAppointment = $this->upcomingAppointments?->first();

        return [
            'user' => [
                'id' => $this->id,
                'name' => $this->name,
                'phone' => $this->phone,
                'email' => $this->email,
                'avatar_url' => $this->avatar_url,
            ],
            'profile_complete' => $this->has_complete_profile,
            'has_profile' => $this->profile !== null,
            'upcoming_appointments' => $this->upcomingAppointments
                ? AppointmentResource::collection($this->upcomingAppointments)
                : [],
            'statistics' => [
                'total_appointments' => $this->total_appointments,
                'upcoming_count' => $this->upcoming_appointments_count,
                'completed_count' => $this->completed_appointments_count,
                'last_visit' => $this->last_visit?->toDateString(),
            ],
            'next_appointment' => $nextAppointment ? [
                'id' => $nextAppointment->id,
                'date' => $nextAppointment->formatted_date,
                'time' => $nextAppointment->formatted_time,
                'day_name' => $nextAppointment->day_name,
                'status' => $nextAppointment->status->value,
                'status_label' => $nextAppointment->status_label,
            ] : null,
        ];
    }
}
