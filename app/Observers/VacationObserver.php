<?php

namespace App\Observers;

use App\Models\Vacation;
use App\Services\CacheInvalidationService;

class VacationObserver
{
    public function __construct(
        protected CacheInvalidationService $cacheService
    ) {}

    /**
     * Handle the Vacation "created" event.
     */
    public function created(Vacation $vacation): void
    {
        $this->cacheService->onVacationChanged();
    }

    /**
     * Handle the Vacation "updated" event.
     */
    public function updated(Vacation $vacation): void
    {
        $this->cacheService->onVacationChanged();
    }

    /**
     * Handle the Vacation "deleted" event.
     */
    public function deleted(Vacation $vacation): void
    {
        $this->cacheService->onVacationChanged();
    }

    /**
     * Handle the Vacation "restored" event.
     */
    public function restored(Vacation $vacation): void
    {
        $this->cacheService->onVacationChanged();
    }

    /**
     * Handle the Vacation "force deleted" event.
     */
    public function forceDeleted(Vacation $vacation): void
    {
        $this->cacheService->onVacationChanged();
    }
}
