<?php

namespace App\Policies;

use App\Models\MedicalRecord;
use App\Models\User;

class MedicalRecordPolicy
{
    /**
     * Staff can view any medical record list
     */
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    /**
     * Staff can view any record, patients can view their own
     */
    public function view(User $user, MedicalRecord $medicalRecord): bool
    {
        if ($user->isStaff()) {
            return true;
        }

        return $user->id === $medicalRecord->patient_id;
    }

    /**
     * Only staff can create medical records
     */
    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    /**
     * Only staff can update medical records
     */
    public function update(User $user, MedicalRecord $medicalRecord): bool
    {
        return $user->isStaff();
    }

    /**
     * Only admin can delete medical records
     */
    public function delete(User $user, MedicalRecord $medicalRecord): bool
    {
        return $user->isAdmin();
    }
}
