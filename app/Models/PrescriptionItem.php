<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrescriptionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'prescription_id',
        'medication_name',
        'dosage',
        'frequency',
        'duration',
        'instructions',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    // ==================== Relationships ====================

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    // ==================== Accessors ====================

    public function getFullDosageTextAttribute(): string
    {
        return "{$this->medication_name} {$this->dosage} - {$this->frequency} لمدة {$this->duration}";
    }

    public function getFullDescriptionAttribute(): string
    {
        $description = $this->full_dosage_text;

        if ($this->instructions) {
            $description .= " ({$this->instructions})";
        }

        if ($this->quantity) {
            $description .= " - الكمية: {$this->quantity}";
        }

        return $description;
    }
}
