<?php

namespace App\Observers;

use App\Models\Schedule;
use App\Services\CacheInvalidationService;

class ScheduleObserver
{
    public function __construct(
        protected CacheInvalidationService $cacheService
    ) {}

    /**
     * Handle the Schedule "created" event.
     */
    public function created(Schedule $schedule): void
    {
        $this->cacheService->onScheduleChanged();
    }

    /**
     * Handle the Schedule "updated" event.
     */
    public function updated(Schedule $schedule): void
    {
        $this->cacheService->onScheduleChanged();
    }

    /**
     * Handle the Schedule "deleted" event.
     */
    public function deleted(Schedule $schedule): void
    {
        $this->cacheService->onScheduleChanged();
    }

    /**
     * Handle the Schedule "restored" event.
     */
    public function restored(Schedule $schedule): void
    {
        $this->cacheService->onScheduleChanged();
    }

    /**
     * Handle the Schedule "force deleted" event.
     */
    public function forceDeleted(Schedule $schedule): void
    {
        $this->cacheService->onScheduleChanged();
    }
}
