<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ClinicSetting extends Model
{
    use HasFactory;

    /**
     * Cache key for clinic settings.
     */
    public const CACHE_KEY = 'clinic_settings';

    /**
     * Cache TTL in seconds (1 hour).
     */
    public const CACHE_TTL = 3600;

    protected $fillable = [
        'clinic_name',
        'doctor_name',
        'specialization',
        'phone',
        'email',
        'address',
        'logo',
        'slot_duration',
        'max_patients_per_slot',
        'advance_booking_days',
        'cancellation_hours',
    ];

    protected $casts = [
        'slot_duration' => 'integer',
        'max_patients_per_slot' => 'integer',
        'advance_booking_days' => 'integer',
        'cancellation_hours' => 'integer',
    ];

    /**
     * Boot the model and set up cache invalidation.
     */
    protected static function booted(): void
    {
        // Clear cache when settings are updated
        static::updated(function () {
            static::clearCache();
        });

        // Clear cache when settings are deleted (unlikely but for completeness)
        static::deleted(function () {
            static::clearCache();
        });
    }

    /**
     * Get the singleton instance of clinic settings (cached).
     */
    public static function getInstance(): self
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            $settings = self::first();

            if (!$settings) {
                $settings = self::create([
                    'clinic_name' => 'العيادة',
                    'doctor_name' => 'الدكتور',
                    'slot_duration' => 30,
                    'max_patients_per_slot' => 1,
                    'advance_booking_days' => 30,
                    'cancellation_hours' => 24,
                ]);
            }

            return $settings;
        });
    }

    /**
     * Clear the cached settings.
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Get fresh instance (bypassing cache).
     */
    public static function getFreshInstance(): self
    {
        self::clearCache();

        return self::getInstance();
    }

    /**
     * Get the full URL for the logo.
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo) {
            return null;
        }

        return Storage::disk('public')->url($this->logo);
    }

    /**
     * Get the maximum booking date based on advance_booking_days.
     */
    public function getMaxBookingDate(): \Carbon\Carbon
    {
        return now()->addDays($this->advance_booking_days);
    }

    /**
     * Get the cancellation deadline for an appointment.
     */
    public function getCancellationDeadline(\Carbon\Carbon $appointmentTime): \Carbon\Carbon
    {
        return $appointmentTime->copy()->subHours($this->cancellation_hours);
    }

    /**
     * Check if an appointment can be cancelled.
     */
    public function canCancelAppointment(\Carbon\Carbon $appointmentTime): bool
    {
        return now()->lt($this->getCancellationDeadline($appointmentTime));
    }

    /**
     * Get a setting value with a default fallback.
     * Maps between getSetting keys and model attributes.
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        // Map common getSetting keys to model attributes
        $keyMap = [
            'clinic_name' => 'clinic_name',
            'clinic_address' => 'address',
            'clinic_phone' => 'phone',
            'clinic_email' => 'email',
            'doctor_name' => 'doctor_name',
            'specialization' => 'specialization',
            'logo' => 'logo',
            'slot_duration' => 'slot_duration',
            'max_patients_per_slot' => 'max_patients_per_slot',
            'advance_booking_days' => 'advance_booking_days',
            'cancellation_hours' => 'cancellation_hours',
        ];

        $attribute = $keyMap[$key] ?? $key;

        return $this->getAttribute($attribute) ?? $default;
    }
}
