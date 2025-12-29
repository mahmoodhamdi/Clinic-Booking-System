<?php

namespace App\Policies;

use App\Models\Prescription;
use App\Models\User;

class PrescriptionPolicy
{
    /**
     * Staff can view any prescription list
     */
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    /**
     * Staff can view any prescription, patients can view their own
     */
    public function view(User $user, Prescription $prescription): bool
    {
        if ($user->isStaff()) {
            return true;
        }

        // Load the medical record relationship if not loaded
        $prescription->loadMissing('medicalRecord');

        return $user->id === $prescription->medicalRecord->patient_id;
    }

    /**
     * Only staff can create prescriptions
     */
    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    /**
     * Only staff can update prescriptions
     */
    public function update(User $user, Prescription $prescription): bool
    {
        return $user->isStaff();
    }

    /**
     * Only admin can delete prescriptions
     */
    public function delete(User $user, Prescription $prescription): bool
    {
        return $user->isAdmin();
    }

    /**
     * Only staff can dispense prescriptions
     */
    public function dispense(User $user, Prescription $prescription): bool
    {
        return $user->isStaff();
    }

    /**
     * Staff can view any prescription PDF, patients can view their own
     */
    public function downloadPdf(User $user, Prescription $prescription): bool
    {
        if ($user->isStaff()) {
            return true;
        }

        $prescription->loadMissing('medicalRecord');

        return $user->id === $prescription->medicalRecord->patient_id;
    }
}
