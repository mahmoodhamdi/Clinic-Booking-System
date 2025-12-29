<?php

namespace App\Policies;

use App\Models\PatientProfile;
use App\Models\User;

class PatientProfilePolicy
{
    /**
     * Staff can view any patient profile list
     */
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    /**
     * Staff can view any profile, patients can view their own
     */
    public function view(User $user, PatientProfile $patientProfile): bool
    {
        if ($user->isStaff()) {
            return true;
        }

        return $user->id === $patientProfile->user_id;
    }

    /**
     * Only patients can create their own profile
     */
    public function create(User $user): bool
    {
        return $user->isPatient();
    }

    /**
     * Staff can update any profile, patients can update their own
     */
    public function update(User $user, PatientProfile $patientProfile): bool
    {
        if ($user->isStaff()) {
            return true;
        }

        return $user->id === $patientProfile->user_id;
    }

    /**
     * Only admin can delete patient profiles
     */
    public function delete(User $user, PatientProfile $patientProfile): bool
    {
        return $user->isAdmin();
    }
}
