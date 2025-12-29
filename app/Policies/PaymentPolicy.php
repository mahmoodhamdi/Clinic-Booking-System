<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    /**
     * Staff can view any payment list
     */
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    /**
     * Staff can view any payment, patients can view their own
     */
    public function view(User $user, Payment $payment): bool
    {
        if ($user->isStaff()) {
            return true;
        }

        // Load the appointment relationship if not loaded
        $payment->loadMissing('appointment');

        return $user->id === $payment->appointment->user_id;
    }

    /**
     * Only staff can create payments
     */
    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    /**
     * Only staff can update payments
     */
    public function update(User $user, Payment $payment): bool
    {
        return $user->isStaff();
    }

    /**
     * Only admin can refund payments
     */
    public function refund(User $user, Payment $payment): bool
    {
        return $user->isAdmin();
    }

    /**
     * Only staff can mark payment as paid
     */
    public function markAsPaid(User $user, Payment $payment): bool
    {
        return $user->isStaff();
    }
}
