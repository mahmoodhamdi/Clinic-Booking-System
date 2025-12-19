<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prescription extends Model
{
    use HasFactory;

    protected $fillable = [
        'medical_record_id',
        'prescription_number',
        'notes',
        'valid_until',
        'is_dispensed',
        'dispensed_at',
    ];

    protected $casts = [
        'valid_until' => 'date',
        'is_dispensed' => 'boolean',
        'dispensed_at' => 'datetime',
    ];

    // ==================== Boot ====================

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($prescription) {
            if (empty($prescription->prescription_number)) {
                $prescription->prescription_number = self::generateNumber();
            }
        });
    }

    // ==================== Relationships ====================

    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalRecord::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PrescriptionItem::class);
    }

    // ==================== Accessors ====================

    public function getIsValidAttribute(): bool
    {
        if ($this->valid_until === null) {
            return true;
        }

        return $this->valid_until->isFuture() || $this->valid_until->isToday();
    }

    public function getIsExpiredAttribute(): bool
    {
        return !$this->is_valid;
    }

    public function getItemsCountAttribute(): int
    {
        return $this->items()->count();
    }

    public function getPatientAttribute(): ?User
    {
        return $this->medicalRecord?->patient;
    }

    // ==================== Scopes ====================

    public function scopeDispensed($query)
    {
        return $query->where('is_dispensed', true);
    }

    public function scopeNotDispensed($query)
    {
        return $query->where('is_dispensed', false);
    }

    public function scopeValid($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('valid_until')
                ->orWhereDate('valid_until', '>=', now()->toDateString());
        });
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('valid_until')
            ->whereDate('valid_until', '<', now()->toDateString());
    }

    public function scopeForPatient($query, int $patientId)
    {
        return $query->whereHas('medicalRecord', function ($q) use ($patientId) {
            $q->where('patient_id', $patientId);
        });
    }

    // ==================== Methods ====================

    public function markAsDispensed(): self
    {
        $this->update([
            'is_dispensed' => true,
            'dispensed_at' => now(),
        ]);

        return $this;
    }

    public static function generateNumber(): string
    {
        $year = now()->year;
        $lastPrescription = self::whereYear('created_at', $year)
            ->orderByDesc('id')
            ->first();

        if ($lastPrescription) {
            $lastNumber = (int) substr($lastPrescription->prescription_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf('RX-%d-%04d', $year, $newNumber);
    }
}
