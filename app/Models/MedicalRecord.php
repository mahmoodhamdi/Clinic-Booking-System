<?php

namespace App\Models;

use App\Models\Traits\BelongsToPatient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MedicalRecord extends Model
{
    use BelongsToPatient, HasFactory, SoftDeletes;

    /**
     * Override the patient foreign key for this model.
     */
    public function getPatientForeignKey(): string
    {
        return 'patient_id';
    }

    protected $fillable = [
        'appointment_id',
        'patient_id',
        'diagnosis',
        'symptoms',
        'examination_notes',
        'treatment_plan',
        'follow_up_date',
        'follow_up_notes',
        'vital_signs',
    ];

    protected $casts = [
        'follow_up_date' => 'date',
        'vital_signs' => 'array',
    ];

    // ==================== Relationships ====================

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    // ==================== Accessors ====================

    public function getHasFollowUpAttribute(): bool
    {
        return $this->follow_up_date !== null;
    }

    public function getFollowUpFormattedAttribute(): ?string
    {
        return $this->follow_up_date?->format('Y-m-d');
    }

    public function getBloodPressureAttribute(): ?string
    {
        return $this->vital_signs['blood_pressure'] ?? null;
    }

    public function getHeartRateAttribute(): ?int
    {
        return $this->vital_signs['heart_rate'] ?? null;
    }

    public function getTemperatureAttribute(): ?float
    {
        return $this->vital_signs['temperature'] ?? null;
    }

    public function getWeightAttribute(): ?float
    {
        return $this->vital_signs['weight'] ?? null;
    }

    public function getHeightAttribute(): ?float
    {
        return $this->vital_signs['height'] ?? null;
    }

    public function getBmiAttribute(): ?float
    {
        $weight = $this->weight;
        $height = $this->height;

        if ($weight && $height) {
            $heightInMeters = $height / 100;
            return round($weight / ($heightInMeters * $heightInMeters), 1);
        }

        return null;
    }

    public function getPrescriptionsCountAttribute(): int
    {
        return $this->prescriptions()->count();
    }

    public function getAttachmentsCountAttribute(): int
    {
        return $this->attachments()->count();
    }

    // ==================== Scopes ====================

    public function scopeForPatient($query, int $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeWithFollowUp($query)
    {
        return $query->whereNotNull('follow_up_date');
    }

    public function scopeFollowUpDue($query)
    {
        return $query->whereNotNull('follow_up_date')
            ->whereDate('follow_up_date', '<=', now()->addDays(7));
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeWithDueFollowUps(Builder $query): Builder
    {
        return $query->whereNotNull('follow_up_date')
            ->where('follow_up_date', '<=', now()->toDateString());
    }

    public function scopeRecentFirst(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'desc');
    }
}
