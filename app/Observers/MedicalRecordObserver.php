<?php

namespace App\Observers;

use App\Models\MedicalRecord;
use App\Services\CacheInvalidationService;

class MedicalRecordObserver
{
    public function __construct(
        protected CacheInvalidationService $cacheService
    ) {}

    /**
     * Handle the MedicalRecord "created" event.
     */
    public function created(MedicalRecord $medicalRecord): void
    {
        $this->cacheService->onMedicalRecordChanged();
    }

    /**
     * Handle the MedicalRecord "updated" event.
     */
    public function updated(MedicalRecord $medicalRecord): void
    {
        $this->cacheService->onMedicalRecordChanged();
    }

    /**
     * Handle the MedicalRecord "deleted" event.
     */
    public function deleted(MedicalRecord $medicalRecord): void
    {
        $this->cacheService->onMedicalRecordChanged();
    }

    /**
     * Handle the MedicalRecord "restored" event.
     */
    public function restored(MedicalRecord $medicalRecord): void
    {
        $this->cacheService->onMedicalRecordChanged();
    }

    /**
     * Handle the MedicalRecord "force deleted" event.
     */
    public function forceDeleted(MedicalRecord $medicalRecord): void
    {
        $this->cacheService->onMedicalRecordChanged();
    }
}
