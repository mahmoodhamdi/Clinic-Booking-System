<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'appointment_id',
        'amount',
        'discount',
        'total',
        'method',
        'status',
        'transaction_id',
        'notes',
        'paid_at',
        'refunded_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'method' => PaymentMethod::class,
        'status' => PaymentStatus::class,
        'paid_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    // ==================== Relationships ====================

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    // ==================== Accessors ====================

    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2) . ' ج.م';
    }

    public function getFormattedDiscountAttribute(): string
    {
        return number_format($this->discount, 2) . ' ج.م';
    }

    public function getFormattedTotalAttribute(): string
    {
        return number_format($this->total, 2) . ' ج.م';
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status->labelAr();
    }

    public function getMethodLabelAttribute(): string
    {
        return $this->method->labelAr();
    }

    public function getPatientAttribute(): ?User
    {
        return $this->appointment?->patient;
    }

    public function getHasDiscountAttribute(): bool
    {
        return $this->discount > 0;
    }

    public function getDiscountPercentageAttribute(): float
    {
        if ($this->amount <= 0) {
            return 0;
        }

        return round(($this->discount / $this->amount) * 100, 1);
    }

    // ==================== Scopes ====================

    public function scopePaid($query)
    {
        return $query->where('status', PaymentStatus::PAID);
    }

    public function scopePending($query)
    {
        return $query->where('status', PaymentStatus::PENDING);
    }

    public function scopeRefunded($query)
    {
        return $query->where('status', PaymentStatus::REFUNDED);
    }

    public function scopeForPatient($query, int $patientId)
    {
        return $query->whereHas('appointment', function ($q) use ($patientId) {
            $q->where('user_id', $patientId);
        });
    }

    public function scopeForDateRange($query, string $from, string $to)
    {
        return $query->whereBetween('paid_at', [$from . ' 00:00:00', $to . ' 23:59:59']);
    }

    public function scopeByMethod($query, PaymentMethod $method)
    {
        return $query->where('method', $method);
    }

    public function scopePaidBetween($query, string $from, string $to)
    {
        return $query->paid()
            ->whereBetween('paid_at', [$from, $to]);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', now()->toDateString());
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);
    }

    public function scopeUnpaid(Builder $query): Builder
    {
        return $query->where('status', PaymentStatus::PENDING);
    }

    public function scopeForPeriod(Builder $query, Carbon $from, Carbon $to): Builder
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    // ==================== Methods ====================

    public function isPending(): bool
    {
        return $this->status === PaymentStatus::PENDING;
    }

    public function isPaid(): bool
    {
        return $this->status === PaymentStatus::PAID;
    }

    public function isRefunded(): bool
    {
        return $this->status === PaymentStatus::REFUNDED;
    }

    public function markAsPaid(?string $transactionId = null): self
    {
        $this->update([
            'status' => PaymentStatus::PAID,
            'paid_at' => now(),
            'transaction_id' => $transactionId,
        ]);

        return $this;
    }

    public function refund(?string $reason = null): self
    {
        $updateData = [
            'status' => PaymentStatus::REFUNDED,
            'refunded_at' => now(),
        ];

        if ($reason) {
            $updateData['notes'] = $reason;
        }

        $this->update($updateData);

        return $this;
    }

    public static function calculateTotal(float $amount, float $discount = 0): float
    {
        return max(0, $amount - $discount);
    }
}
