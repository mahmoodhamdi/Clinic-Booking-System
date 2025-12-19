<?php

namespace App\Services;

use App\Enums\DayOfWeek;
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

        // Map to full slot info
        return $slots->map(function ($time) use ($date) {
            return [
                'time' => $time,
                'datetime' => $date->copy()->setTimeFromTimeString($time)->toIso8601String(),
                'is_available' => true, // Will be updated in Phase 3 with appointment check
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

        // TODO: In Phase 3, check if slot is already booked
        // $isBooked = Appointment::where('appointment_time', $datetime)
        //     ->whereIn('status', ['pending', 'confirmed'])
        //     ->exists();

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

            if ($slots->isNotEmpty()) {
                $firstSlot = $slots->first();
                return [
                    'date' => $date->toDateString(),
                    'day_name' => DayOfWeek::fromDate($date)->labelAr(),
                    'time' => $firstSlot['time'],
                    'datetime' => $firstSlot['datetime'],
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
        $availableDates = 0;

        for ($i = 0; $i <= $days; $i++) {
            $date = now()->addDays($i);

            if ($this->isDateAvailable($date)) {
                $availableDates++;
                $totalSlots += $this->getSlotsForDate($date)->count();
            }
        }

        return [
            'total_days' => $days + 1,
            'available_dates' => $availableDates,
            'total_slots' => $totalSlots,
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
