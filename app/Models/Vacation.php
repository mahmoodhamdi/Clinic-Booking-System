<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Vacation extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'start_date',
        'end_date',
        'reason',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Scope to get upcoming vacations.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('end_date', '>=', now()->toDateString())
            ->orderBy('start_date');
    }

    /**
     * Scope to get currently active vacations.
     */
    public function scopeActive($query)
    {
        $today = now()->toDateString();
        return $query->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today);
    }

    /**
     * Scope to get vacations that include a specific date.
     */
    public function scopeForDate($query, Carbon|string $date)
    {
        $checkDate = $date instanceof Carbon ? $date->startOfDay() : Carbon::parse($date)->startOfDay();
        return $query->whereDate('start_date', '<=', $checkDate)
            ->whereDate('end_date', '>=', $checkDate);
    }

    /**
     * Check if this vacation is currently active.
     */
    public function isActive(): bool
    {
        $today = now()->startOfDay();
        return $this->start_date->lte($today) && $this->end_date->gte($today);
    }

    /**
     * Check if this vacation is in the future.
     */
    public function isFuture(): bool
    {
        return $this->start_date->gt(now());
    }

    /**
     * Check if this vacation is in the past.
     */
    public function isPast(): bool
    {
        return $this->end_date->lt(now()->startOfDay());
    }

    /**
     * Check if this vacation includes a specific date.
     */
    public function includesDate(Carbon|string $date): bool
    {
        $checkDate = $date instanceof Carbon ? $date : Carbon::parse($date);
        return $checkDate->between($this->start_date, $this->end_date);
    }

    /**
     * Check if this vacation overlaps with given date range.
     */
    public function overlaps(Carbon|string $startDate, Carbon|string $endDate): bool
    {
        $start = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $end = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);

        return $this->start_date->lte($end) && $this->end_date->gte($start);
    }

    /**
     * Get the number of days in this vacation.
     */
    public function getDaysCountAttribute(): int
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    /**
     * Check if a date is a vacation day.
     */
    public static function isVacationDay(Carbon|string $date): bool
    {
        return self::forDate($date)->exists();
    }
}
