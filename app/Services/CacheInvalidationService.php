<?php

namespace App\Services;

use App\Traits\LogsActivity;
use Illuminate\Support\Facades\Cache;

/**
 * Centralized cache invalidation service.
 *
 * Coordinates cache invalidation across different services
 * when data changes occur (through model observers or manual triggers).
 */
class CacheInvalidationService
{
    use LogsActivity;

    protected SlotGeneratorService $slotService;
    protected DashboardService $dashboardService;

    public function __construct(
        SlotGeneratorService $slotService,
        DashboardService $dashboardService
    ) {
        $this->slotService = $slotService;
        $this->dashboardService = $dashboardService;
    }

    /**
     * Invalidate all application caches.
     */
    public function invalidateAll(): void
    {
        $this->invalidateSlotCache();
        $this->invalidateDashboardCache();
        $this->invalidateScheduleCache();
        $this->invalidateVacationCache();

        $this->logInfo('All caches invalidated');
    }

    /**
     * Invalidate slot-related caches.
     * Call when: appointments created/updated/deleted
     */
    public function invalidateSlotCache(): void
    {
        $this->slotService->invalidateCache();
        $this->logInfo('Slot cache invalidated');
    }

    /**
     * Invalidate dashboard statistics cache.
     * Call when: appointments, payments, medical records change
     */
    public function invalidateDashboardCache(): void
    {
        $this->dashboardService->invalidateCache();
        $this->logInfo('Dashboard cache invalidated');
    }

    /**
     * Invalidate schedule-related caches.
     * Call when: schedules created/updated/deleted
     */
    public function invalidateScheduleCache(): void
    {
        for ($i = 0; $i <= 6; $i++) {
            Cache::forget("schedule_day_{$i}");
        }
        $this->logInfo('Schedule cache invalidated');
    }

    /**
     * Invalidate vacation-related caches.
     * Call when: vacations created/updated/deleted
     */
    public function invalidateVacationCache(): void
    {
        // Invalidate vacation cache for upcoming days
        $days = config('clinic.booking.advance_days', 30);
        for ($i = 0; $i <= $days; $i++) {
            $date = now()->addDays($i)->toDateString();
            Cache::forget("vacation_{$date}");
        }
        $this->logInfo('Vacation cache invalidated');
    }

    /**
     * Invalidate appointment-related caches.
     * Call when: appointments created/updated/deleted
     */
    public function onAppointmentChanged(): void
    {
        $this->invalidateDashboardCache();
        // Note: Slot availability is not cached, only schedules and vacations are
        $this->logInfo('Appointment-related caches invalidated');
    }

    /**
     * Invalidate payment-related caches.
     * Call when: payments created/updated/deleted
     */
    public function onPaymentChanged(): void
    {
        $this->invalidateDashboardCache();
        $this->logInfo('Payment-related caches invalidated');
    }

    /**
     * Invalidate medical record-related caches.
     * Call when: medical records created/updated/deleted
     */
    public function onMedicalRecordChanged(): void
    {
        $this->invalidateDashboardCache();
        $this->logInfo('Medical record-related caches invalidated');
    }

    /**
     * Invalidate schedule-related caches.
     * Call when: schedules created/updated/deleted
     */
    public function onScheduleChanged(): void
    {
        $this->invalidateScheduleCache();
        $this->invalidateSlotCache();
        $this->logInfo('Schedule-related caches invalidated');
    }

    /**
     * Invalidate vacation-related caches.
     * Call when: vacations created/updated/deleted
     */
    public function onVacationChanged(): void
    {
        $this->invalidateVacationCache();
        $this->invalidateSlotCache();
        $this->logInfo('Vacation-related caches invalidated');
    }

    /**
     * Invalidate patient-related caches.
     * Call when: users (patients) created/updated/deleted
     */
    public function onPatientChanged(): void
    {
        $this->invalidateDashboardCache();
        $this->logInfo('Patient-related caches invalidated');
    }
}
