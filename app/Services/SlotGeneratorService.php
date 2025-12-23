<?php

namespace App\Services;

use App\Enums\DayOfWeek;
use App\Models\Appointment;
use App\Models\ClinicSetting;
use App\Models\Schedule;
use App\Models\Vacation;
use App\Traits\LogsActivity;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SlotGeneratorService
{
    use LogsActivity;

    protected ClinicSetting $settings;
    protected int $cacheTtl;

    public function __construct()
    {
        $this->settings = ClinicSetting::getInstance();
        $this->cacheTtl = config('clinic.cache.slots_ttl', 300);
    }

    /**
     * Get cache key for schedule data.
     */
    protected function getScheduleCacheKey(int $dayOfWeek): string
    {
        return "schedule_day_{$dayOfWeek}";
    }

    /**
     * Get cache key for vacation data.
     */
    protected function getVacationCacheKey(string $date): string
    {
        return "vacation_{$date}";
    }

    /**
     * Invalidate all slot-related cache.
     */
    public function invalidateCache(): void
    {
        // Invalidate schedule cache for all days
        for ($i = 0; $i <= 6; $i++) {
            Cache::forget($this->getScheduleCacheKey($i));
        }

        // Invalidate vacation cache for upcoming days
        $days = $this->settings->advance_booking_days ?? 30;
        for ($i = 0; $i <= $days; $i++) {
            $date = now()->addDays($i)->toDateString();
            Cache::forget($this->getVacationCacheKey($date));
        }

        $this->logInfo('Slot cache invalidated');
    }

    /**
     * Get all available dates for booking.
     */
    public function getAvailableDates(?int $days = null): Collection
    {
        $days = $days ?? $this->settings->advance_booking_days;
        $dates = collect();

        for ($i = 0; $i <= $days; $i++) {
            $date = now()->addDays($i);

            if ($this->isDateAvailable($date)) {
                $dates->push([
                    'date' => $date->toDateString(),
                    'day_name' => DayOfWeek::fromDate($date)->labelAr(),
                    'day_name_en' => DayOfWeek::fromDate($date)->label(),
                    'slots_count' => $this->getSlotsForDate($date)->count(),
                ]);
            }
        }

        return $dates;
    }

    /**
     * Check if a date is available for booking.
     */
    public function isDateAvailable(Carbon $date): bool
    {
        $dateString = $date->toDateString();

        // Check if it's a vacation day (cached)
        $isVacation = Cache::remember(
            $this->getVacationCacheKey($dateString),
            $this->cacheTtl,
            fn() => Vacation::isVacationDay($date)
        );

        if ($isVacation) {
            return false;
        }

        // Check if there's an active schedule for this day (cached)
        $dayOfWeek = DayOfWeek::fromDate($date)->value;
        $schedule = Cache::remember(
            $this->getScheduleCacheKey($dayOfWeek),
            $this->cacheTtl,
            fn() => Schedule::active()->forDay(DayOfWeek::from($dayOfWeek))->first()
        );

        return $schedule !== null;
    }

    /**
     * Get available slots for a specific date.
     */
    public function getSlotsForDate(Carbon $date): Collection
    {
        $dateString = $date->toDateString();

        // Check if it's a vacation day (cached)
        $isVacation = Cache::remember(
            $this->getVacationCacheKey($dateString),
            $this->cacheTtl,
            fn() => Vacation::isVacationDay($date)
        );

        if ($isVacation) {
            return collect();
        }

        // Get schedule for this day (cached)
        $dayOfWeek = DayOfWeek::fromDate($date)->value;
        $schedule = Cache::remember(
            $this->getScheduleCacheKey($dayOfWeek),
            $this->cacheTtl,
            fn() => Schedule::active()->forDay(DayOfWeek::from($dayOfWeek))->first()
        );

        if (!$schedule) {
            return collect();
        }

        // Generate slots (from cached schedule)
        $slots = $schedule->generateSlots($this->settings->slot_duration);

        // Filter out past slots if it's today
        if ($date->isToday()) {
            $slots = $slots->filter(function ($time) {
                return Carbon::parse($time)->gt(now());
            });
        }

        // Map to full slot info with availability check
        // Note: We don't cache appointment booking status as it changes frequently
        return $slots->map(function ($time) use ($date) {
            $isBooked = Appointment::isSlotBooked($date, $time);
            return [
                'time' => $time,
                'datetime' => $date->copy()->setTimeFromTimeString($time)->toIso8601String(),
                'is_available' => !$isBooked,
            ];
        })->values();
    }

    /**
     * Check if a specific slot is available.
     */
    public function isSlotAvailable(Carbon $datetime): bool
    {
        $date = $datetime->copy()->startOfDay();
        $time = $datetime->format('H:i');

        // Check if date is available
        if (!$this->isDateAvailable($date)) {
            return false;
        }

        // Check if time slot exists
        $slots = $this->getSlotsForDate($date);
        $slotExists = $slots->contains('time', $time);

        if (!$slotExists) {
            return false;
        }

        // Check if slot is not past
        if ($datetime->lt(now())) {
            return false;
        }

        // Check if slot is already booked
        if (Appointment::isSlotBooked($date, $time)) {
            return false;
        }

        return true;
    }

    /**
     * Get the next available slot.
     */
    public function getNextAvailableSlot(): ?array
    {
        $days = $this->settings->advance_booking_days;

        for ($i = 0; $i <= $days; $i++) {
            $date = now()->addDays($i);
            $slots = $this->getSlotsForDate($date);

            // Find first available slot (not booked)
            $availableSlot = $slots->first(fn($slot) => $slot['is_available']);

            if ($availableSlot) {
                return [
                    'date' => $date->toDateString(),
                    'day_name' => DayOfWeek::fromDate($date)->labelAr(),
                    'time' => $availableSlot['time'],
                    'datetime' => $availableSlot['datetime'],
                ];
            }
        }

        return null;
    }

    /**
     * Get slots summary for a date range.
     */
    public function getSlotsSummary(?int $days = null): array
    {
        $days = $days ?? $this->settings->advance_booking_days;
        $totalSlots = 0;
        $availableSlots = 0;
        $availableDates = 0;

        for ($i = 0; $i <= $days; $i++) {
            $date = now()->addDays($i);

            if ($this->isDateAvailable($date)) {
                $availableDates++;
                $slots = $this->getSlotsForDate($date);
                $totalSlots += $slots->count();
                $availableSlots += $slots->where('is_available', true)->count();
            }
        }

        return [
            'total_days' => $days + 1,
            'available_dates' => $availableDates,
            'total_slots' => $totalSlots,
            'available_slots' => $availableSlots,
            'next_available' => $this->getNextAvailableSlot(),
        ];
    }

    /**
     * Get clinic settings.
     */
    public function getSettings(): ClinicSetting
    {
        return $this->settings;
    }

    /**
     * Refresh settings (useful after update).
     */
    public function refreshSettings(): self
    {
        $this->settings = ClinicSetting::getInstance()->fresh();
        return $this;
    }
}
