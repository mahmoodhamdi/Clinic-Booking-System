<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToPatient
{
    /**
     * Scope to filter records for the authenticated patient.
     * Use this when you want to automatically scope to the logged-in user.
     */
    public function scopeForAuthenticatedPatient(Builder $query): Builder
    {
        return $query->where($this->getPatientForeignKey(), auth()->id());
    }

    /**
     * Get the foreign key column name for patient relationship.
     * Override in models that use a different column name.
     */
    public function getPatientForeignKey(): string
    {
        return 'user_id';
    }

    /**
     * Check if this record belongs to the given patient.
     */
    public function belongsToPatient(int $patientId): bool
    {
        return $this->{$this->getPatientForeignKey()} === $patientId;
    }

    /**
     * Check if this record belongs to the authenticated patient.
     */
    public function belongsToAuthenticatedPatient(): bool
    {
        return $this->belongsToPatient((int) auth()->id());
    }
}
