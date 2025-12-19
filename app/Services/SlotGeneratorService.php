<?php

namespace App\Services;

use App\Enums\DayOfWeek;
use App\Models\Appointment;
use App\Models\ClinicSetting;
use App\Models\Schedule;
use App\Models\Vacation;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SlotGeneratorService
{
    protected ClinicSetting $settings;

    public function __construct()
    {
        $this->settings = ClinicSetting::getInstance();
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
        // Check if it's a vacation day
        if (Vacation::isVacationDay($date)) {
            return false;
        }

        // Check if there's an active schedule for this day
        $schedule = Schedule::active()
            ->forDay(DayOfWeek::fromDate($date))
            ->first();

        return $schedule !== null;
    }

    /**
     * Get available slots for a specific date.
     */
    public function getSlotsForDate(Carbon $date): Collection
    {
        // Check if it's a vacation day
        if (Vacation::isVacationDay($date)) {
            return collect();
        }

        // Get schedule for this day
        $schedule = Schedule::active()
            ->forDay(DayOfWeek::fromDate($date))
            ->first();

        if (!$schedule) {
            return collect();
        }

        // Generate slots
        $slots = $schedule->generateSlots($this->settings->slot_duration);

        // Filter out past slots if it's today
        if ($date->isToday()) {
            $slots = $slots->filter(function ($time) {
                return Carbon::parse($time)->gt(now());
            });
        }

        // Map to full slot info with availability check
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
