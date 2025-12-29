<?php

namespace App\Observers;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\CacheInvalidationService;

class UserObserver
{
    public function __construct(
        protected CacheInvalidationService $cacheService
    ) {}

    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        if ($this->isPatient($user)) {
            $this->cacheService->onPatientChanged();
        }
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        if ($this->isPatient($user)) {
            $this->cacheService->onPatientChanged();
        }
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        if ($this->isPatient($user)) {
            $this->cacheService->onPatientChanged();
        }
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        if ($this->isPatient($user)) {
            $this->cacheService->onPatientChanged();
        }
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        if ($this->isPatient($user)) {
            $this->cacheService->onPatientChanged();
        }
    }

    /**
     * Check if the user is a patient.
     */
    protected function isPatient(User $user): bool
    {
        return $user->role === UserRole::PATIENT;
    }
}
