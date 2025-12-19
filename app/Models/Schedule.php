<?php

namespace App\Models;

use App\Enums\DayOfWeek;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'day_of_week',
        'start_time',
        'end_time',
        'is_active',
        'break_start',
        'break_end',
    ];

    protected $casts = [
        'day_of_week' => DayOfWeek::class,
        'is_active' => 'boolean',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'break_start' => 'datetime:H:i',
        'break_end' => 'datetime:H:i',
    ];

    /**
     * Scope to get only active schedules.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get schedule for a specific day.
     */
    public function scopeForDay($query, DayOfWeek|int $day)
    {
        $dayValue = $day instanceof DayOfWeek ? $day->value : $day;
        return $query->where('day_of_week', $dayValue);
    }

    /**
     * Get the day name in Arabic.
     */
    public function getDayNameAttribute(): string
    {
        return $this->day_of_week->labelAr();
    }

    /**
     * Get the day name in English.
     */
    public function getDayNameEnAttribute(): string
    {
        return $this->day_of_week->label();
    }

    /**
     * Get formatted start time.
     */
    public function getFormattedStartTimeAttribute(): string
    {
        return Carbon::parse($this->start_time)->format('H:i');
    }

    /**
     * Get formatted end time.
     */
    public function getFormattedEndTimeAttribute(): string
    {
        return Carbon::parse($this->end_time)->format('H:i');
    }

    /**
     * Get formatted break start time.
     */
    public function getFormattedBreakStartAttribute(): ?string
    {
        return $this->break_start ? Carbon::parse($this->break_start)->format('H:i') : null;
    }

    /**
     * Get formatted break end time.
     */
    public function getFormattedBreakEndAttribute(): ?string
    {
        return $this->break_end ? Carbon::parse($this->break_end)->format('H:i') : null;
    }

    /**
     * Check if schedule has a break.
     */
    public function hasBreak(): bool
    {
        return $this->break_start && $this->break_end;
    }

    /**
     * Generate time slots for this schedule.
     */
    public function generateSlots(int $slotDuration): Collection
    {
        $slots = collect();
        $startTime = Carbon::parse($this->start_time);
        $endTime = Carbon::parse($this->end_time);
        $breakStart = $this->break_start ? Carbon::parse($this->break_start) : null;
        $breakEnd = $this->break_end ? Carbon::parse($this->break_end) : null;

        $current = $startTime->copy();

        while ($current->copy()->addMinutes($slotDuration)->lte($endTime)) {
            $slotEnd = $current->copy()->addMinutes($slotDuration);

            // Skip if slot overlaps with break
            if ($this->hasBreak()) {
                $overlapsBreak = $current->lt($breakEnd) && $slotEnd->gt($breakStart);
                if ($overlapsBreak) {
                    $current = $breakEnd->copy();
                    continue;
                }
            }

            $slots->push($current->format('H:i'));
            $current->addMinutes($slotDuration);
        }

        return $slots;
    }

    /**
     * Get the number of slots for this schedule.
     */
    public function getSlotsCount(int $slotDuration): int
    {
        return $this->generateSlots($slotDuration)->count();
    }
}
