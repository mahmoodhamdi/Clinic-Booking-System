<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    /**
     * Staff can view any appointment
     */
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    /**
     * Staff can view any appointment, patients can view their own
     */
    public function view(User $user, Appointment $appointment): bool
    {
        if ($user->isStaff()) {
            return true;
        }

        return $user->id === $appointment->user_id;
    }

    /**
     * Only patients can create appointments for themselves
     */
    public function create(User $user): bool
    {
        return $user->role === UserRole::PATIENT;
    }

    /**
     * Staff can update any appointment
     */
    public function update(User $user, Appointment $appointment): bool
    {
        return $user->isStaff();
    }

    /**
     * Staff can confirm appointments
     */
    public function confirm(User $user, Appointment $appointment): bool
    {
        return $user->isStaff();
    }

    /**
     * Staff can complete appointments
     */
    public function complete(User $user, Appointment $appointment): bool
    {
        return $user->isStaff();
    }

    /**
     * Staff can cancel any, patients can cancel their own
     */
    public function cancel(User $user, Appointment $appointment): bool
    {
        if ($user->isStaff()) {
            return true;
        }

        return $user->id === $appointment->user_id;
    }

    /**
     * Staff can mark no-show
     */
    public function markNoShow(User $user, Appointment $appointment): bool
    {
        return $user->isStaff();
    }

    /**
     * Staff can update notes
     */
    public function updateNotes(User $user, Appointment $appointment): bool
    {
        return $user->isStaff();
    }
}
