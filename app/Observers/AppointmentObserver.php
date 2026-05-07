<?php

namespace App\Observers;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Services\CacheInvalidationService;
use App\Services\NotificationService;

class AppointmentObserver
{
    public function __construct(
        protected CacheInvalidationService $cacheService,
        protected NotificationService $notifications,
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

        // Fire side-effect notifications on status transitions. wasChanged()
        // returns true only when the status was *actually* updated in this
        // save (not just dirty-then-discarded), so reconfirms or no-op saves
        // don't double-send.
        if ($appointment->wasChanged('status')) {
            $newStatus = $appointment->status;

            if ($newStatus === AppointmentStatus::CONFIRMED) {
                $this->notifications->sendAppointmentConfirmed($appointment);
            }
        }
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
