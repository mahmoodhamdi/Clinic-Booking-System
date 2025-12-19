<?php

namespace App\Models;

use App\Enums\BloodType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'blood_type',
        'emergency_contact_name',
        'emergency_contact_phone',
        'allergies',
        'chronic_diseases',
        'current_medications',
        'medical_notes',
        'insurance_provider',
        'insurance_number',
    ];

    protected $casts = [
        'blood_type' => BloodType::class,
        'allergies' => 'array',
        'chronic_diseases' => 'array',
        'current_medications' => 'array',
    ];

    // ==================== Relationships ====================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ==================== Accessors ====================

    public function getBloodTypeLabelAttribute(): ?string
    {
        return $this->blood_type?->labelAr();
    }

    public function getBloodTypeLabelEnAttribute(): ?string
    {
        return $this->blood_type?->label();
    }

    public function getAllergiesListAttribute(): string
    {
        if (empty($this->allergies)) {
            return '';
        }
        return implode(', ', $this->allergies);
    }

    public function getChronicDiseasesListAttribute(): string
    {
        if (empty($this->chronic_diseases)) {
            return '';
        }
        return implode(', ', $this->chronic_diseases);
    }

    public function getCurrentMedicationsListAttribute(): string
    {
        if (empty($this->current_medications)) {
            return '';
        }
        return implode(', ', $this->current_medications);
    }

    public function getHasEmergencyContactAttribute(): bool
    {
        return !empty($this->emergency_contact_name) && !empty($this->emergency_contact_phone);
    }

    public function getHasInsuranceAttribute(): bool
    {
        return !empty($this->insurance_provider) && !empty($this->insurance_number);
    }

    public function getIsCompleteAttribute(): bool
    {
        return $this->blood_type !== null
            && $this->has_emergency_contact;
    }

    // ==================== Methods ====================

    public function hasAllergy(string $allergy): bool
    {
        if (empty($this->allergies)) {
            return false;
        }
        return in_array(strtolower($allergy), array_map('strtolower', $this->allergies));
    }

    public function hasChronicDisease(string $disease): bool
    {
        if (empty($this->chronic_diseases)) {
            return false;
        }
        return in_array(strtolower($disease), array_map('strtolower', $this->chronic_diseases));
    }

    public function addAllergy(string $allergy): self
    {
        $allergies = $this->allergies ?? [];
        if (!in_array($allergy, $allergies)) {
            $allergies[] = $allergy;
            $this->update(['allergies' => $allergies]);
        }
        return $this;
    }

    public function removeAllergy(string $allergy): self
    {
        $allergies = $this->allergies ?? [];
        $allergies = array_filter($allergies, fn($a) => $a !== $allergy);
        $this->update(['allergies' => array_values($allergies)]);
        return $this;
    }

    public function addChronicDisease(string $disease): self
    {
        $diseases = $this->chronic_diseases ?? [];
        if (!in_array($disease, $diseases)) {
            $diseases[] = $disease;
            $this->update(['chronic_diseases' => $diseases]);
        }
        return $this;
    }

    public function addMedication(string $medication): self
    {
        $medications = $this->current_medications ?? [];
        if (!in_array($medication, $medications)) {
            $medications[] = $medication;
            $this->update(['current_medications' => $medications]);
        }
        return $this;
    }

    public function removeMedication(string $medication): self
    {
        $medications = $this->current_medications ?? [];
        $medications = array_filter($medications, fn($m) => $m !== $medication);
        $this->update(['current_medications' => array_values($medications)]);
        return $this;
    }
}
