<?php

namespace App\Observers;

use App\Models\Appointment;
use App\Services\CacheInvalidationService;

class AppointmentObserver
{
    public function __construct(
        protected CacheInvalidationService $cacheService
    ) {}

    /**
     * Handle the Appointment "created" event.
     */
    public function created(Appointment $appointment): void
    {
        $this->cacheService->onAppointmentChanged();
    }

    /**
     * Handle the Appointment "updated" event.
     */
    public function updated(Appointment $appointment): void
    {
        $this->cacheService->onAppointmentChanged();
    }

    /**
     * Handle the Appointment "deleted" event.
     */
    public function deleted(Appointment $appointment): void
    {
        $this->cacheService->onAppointmentChanged();
    }

    /**
     * Handle the Appointment "restored" event.
     */
    public function restored(Appointment $appointment): void
    {
        $this->cacheService->onAppointmentChanged();
    }

    /**
     * Handle the Appointment "force deleted" event.
     */
    public function forceDeleted(Appointment $appointment): void
    {
        $this->cacheService->onAppointmentChanged();
    }
}
