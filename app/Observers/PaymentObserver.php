<?php

namespace App\Observers;

use App\Models\Payment;
use App\Services\CacheInvalidationService;

class PaymentObserver
{
    public function __construct(
        protected CacheInvalidationService $cacheService
    ) {}

    /**
     * Handle the Payment "created" event.
     */
    public function created(Payment $payment): void
    {
        $this->cacheService->onPaymentChanged();
    }

    /**
     * Handle the Payment "updated" event.
     */
    public function updated(Payment $payment): void
    {
        $this->cacheService->onPaymentChanged();
    }

    /**
     * Handle the Payment "deleted" event.
     */
    public function deleted(Payment $payment): void
    {
        $this->cacheService->onPaymentChanged();
    }

    /**
     * Handle the Payment "restored" event.
     */
    public function restored(Payment $payment): void
    {
        $this->cacheService->onPaymentChanged();
    }

    /**
     * Handle the Payment "force deleted" event.
     */
    public function forceDeleted(Payment $payment): void
    {
        $this->cacheService->onPaymentChanged();
    }
}
