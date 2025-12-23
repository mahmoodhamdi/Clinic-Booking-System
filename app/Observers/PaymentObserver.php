<?php

namespace App\Observers;

use App\Models\Payment;
use Illuminate\Support\Facades\Cache;

class PaymentObserver
{
    /**
     * Handle the Payment "created" event.
     */
    public function created(Payment $payment): void
    {
        $this->invalidateDashboardCache();
    }

    /**
     * Handle the Payment "updated" event.
     */
    public function updated(Payment $payment): void
    {
        $this->invalidateDashboardCache();
    }

    /**
     * Handle the Payment "deleted" event.
     */
    public function deleted(Payment $payment): void
    {
        $this->invalidateDashboardCache();
    }

    /**
     * Handle the Payment "restored" event.
     */
    public function restored(Payment $payment): void
    {
        $this->invalidateDashboardCache();
    }

    /**
     * Handle the Payment "force deleted" event.
     */
    public function forceDeleted(Payment $payment): void
    {
        $this->invalidateDashboardCache();
    }

    /**
     * Invalidate dashboard statistics cache when payments change.
     */
    protected function invalidateDashboardCache(): void
    {
        Cache::forget('dashboard_stats');
    }
}
