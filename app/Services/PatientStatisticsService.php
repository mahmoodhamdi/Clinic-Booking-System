<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PatientStatisticsService
{
    /**
     * Get statistics for a single patient.
     */
    public function getForPatient(User $patient): array
    {
        $stats = $patient->appointments()
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as cancelled,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as no_show,
                SUM(CASE WHEN status IN (?, ?) AND appointment_date >= DATE('now') THEN 1 ELSE 0 END) as upcoming,
                MAX(CASE WHEN status = ? THEN appointment_date ELSE NULL END) as last_visit
            ", [
                AppointmentStatus::COMPLETED->value,
                AppointmentStatus::CANCELLED->value,
                AppointmentStatus::NO_SHOW->value,
                AppointmentStatus::PENDING->value,
                AppointmentStatus::CONFIRMED->value,
                AppointmentStatus::COMPLETED->value,
            ])
            ->first();

        return [
            'total_appointments' => (int) ($stats->total ?? 0),
            'completed_appointments' => (int) ($stats->completed ?? 0),
            'cancelled_appointments' => (int) ($stats->cancelled ?? 0),
            'no_show_count' => (int) ($stats->no_show ?? 0),
            'upcoming_appointments' => (int) ($stats->upcoming ?? 0),
            'last_visit' => $stats->last_visit,
        ];
    }

    /**
     * Get statistics for multiple patients in one query.
     */
    public function getForPatients(Collection $patients): array
    {
        if ($patients->isEmpty()) {
            return [];
        }

        $patientIds = $patients->pluck('id')->toArray();

        $stats = DB::table('appointments')
            ->selectRaw("
                user_id,
                COUNT(*) as total,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as cancelled,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as no_show,
                SUM(CASE WHEN status IN (?, ?) AND appointment_date >= DATE('now') THEN 1 ELSE 0 END) as upcoming,
                MAX(CASE WHEN status = ? THEN appointment_date ELSE NULL END) as last_visit
            ", [
                AppointmentStatus::COMPLETED->value,
                AppointmentStatus::CANCELLED->value,
                AppointmentStatus::NO_SHOW->value,
                AppointmentStatus::PENDING->value,
                AppointmentStatus::CONFIRMED->value,
                AppointmentStatus::COMPLETED->value,
            ])
            ->whereIn('user_id', $patientIds)
            ->whereNull('deleted_at')
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $result = [];
        foreach ($patients as $patient) {
            $patientStats = $stats->get($patient->id);
            $result[$patient->id] = [
                'total_appointments' => (int) ($patientStats->total ?? 0),
                'completed_appointments' => (int) ($patientStats->completed ?? 0),
                'cancelled_appointments' => (int) ($patientStats->cancelled ?? 0),
                'no_show_count' => (int) ($patientStats->no_show ?? 0),
                'upcoming_appointments' => (int) ($patientStats->upcoming ?? 0),
                'last_visit' => $patientStats->last_visit ?? null,
            ];
        }

        return $result;
    }
}
