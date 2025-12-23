<?php

namespace App\Observers;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Support\Facades\Cache;

class AppointmentObserver
{
    /**
     * Handle the Appointment "created" event.
     */
    public function created(Appointment $appointment): void
    {
        $this->invalidateDashboardCache();
    }

    /**
     * Handle the Appointment "updated" event.
     */
    public function updated(Appointment $appointment): void
    {
        $this->invalidateDashboardCache();
    }

    /**
     * Handle the Appointment "deleted" event.
     */
    public function deleted(Appointment $appointment): void
    {
        $this->invalidateDashboardCache();
    }

    /**
     * Handle the Appointment "restored" event.
     */
    public function restored(Appointment $appointment): void
    {
        $this->invalidateDashboardCache();
    }

    /**
     * Handle the Appointment "force deleted" event.
     */
    public function forceDeleted(Appointment $appointment): void
    {
        $this->invalidateDashboardCache();
    }

    /**
     * Invalidate dashboard statistics cache when appointments change.
     */
    protected function invalidateDashboardCache(): void
    {
        Cache::forget('dashboard_stats');
    }
}
