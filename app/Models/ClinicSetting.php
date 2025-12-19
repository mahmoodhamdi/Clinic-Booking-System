<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ClinicSetting extends Model
{
    use HasFactory;

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
     * Get the singleton instance of clinic settings.
     */
    public static function getInstance(): self
    {
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
}
