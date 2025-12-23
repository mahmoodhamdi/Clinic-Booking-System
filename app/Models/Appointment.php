<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use App\Enums\CancelledBy;
use App\Enums\DayOfWeek;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'appointment_date',
        'appointment_time',
        'status',
        'notes',
        'admin_notes',
        'cancellation_reason',
        'cancelled_by',
        'cancelled_at',
        'confirmed_at',
        'completed_at',
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'appointment_time' => 'datetime:H:i',
        'status' => AppointmentStatus::class,
        'cancelled_by' => CancelledBy::class,
        'cancelled_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // ==================== Relationships ====================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Alias for user() - for backwards compatibility and semantic clarity.
     */
    public function patient(): BelongsTo
    {
        return $this->user();
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function medicalRecord(): HasOne
    {
        return $this->hasOne(MedicalRecord::class);
    }

    // ==================== Scopes ====================

    public function scopePending($query)
    {
        return $query->where('status', AppointmentStatus::PENDING);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', AppointmentStatus::CONFIRMED);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', AppointmentStatus::COMPLETED);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', AppointmentStatus::CANCELLED);
    }

    public function scopeNoShow($query)
    {
        return $query->where('status', AppointmentStatus::NO_SHOW);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', AppointmentStatus::activeStatuses());
    }

    public function scopeForDate($query, Carbon|string $date)
    {
        $dateString = $date instanceof Carbon ? $date->toDateString() : $date;
        return $query->whereDate('appointment_date', $dateString);
    }

    public function scopeForPatient($query, int $patientId)
    {
        return $query->where('user_id', $patientId);
    }

    public function scopeUpcoming($query)
    {
        return $query->where(function ($q) {
            $q->whereDate('appointment_date', '>', now()->toDateString())
                ->orWhere(function ($q2) {
                    $q2->whereDate('appointment_date', now()->toDateString())
                        ->whereTime('appointment_time', '>', now()->format('H:i:s'));
                });
        })->active();
    }

    public function scopePast($query)
    {
        return $query->where(function ($q) {
            $q->whereDate('appointment_date', '<', now()->toDateString())
                ->orWhere(function ($q2) {
                    $q2->whereDate('appointment_date', now()->toDateString())
                        ->whereTime('appointment_time', '<=', now()->format('H:i:s'));
                });
        });
    }

    public function scopeToday($query)
    {
        return $query->whereDate('appointment_date', now()->toDateString());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('appointment_date', [
            now()->startOfWeek()->toDateString(),
            now()->endOfWeek()->toDateString(),
        ]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('appointment_date', now()->month)
            ->whereYear('appointment_date', now()->year);
    }

    public function scopeBetweenDates($query, Carbon|string $from, Carbon|string $to)
    {
        $fromDate = $from instanceof Carbon ? $from->toDateString() : $from;
        $toDate = $to instanceof Carbon ? $to->toDateString() : $to;
        return $query->whereBetween('appointment_date', [$fromDate, $toDate]);
    }

    public function scopeForDateRange(Builder $query, Carbon $from, Carbon $to): Builder
    {
        return $query->whereBetween('appointment_date', [$from->toDateString(), $to->toDateString()]);
    }

    public function scopeNotCancelled(Builder $query): Builder
    {
        return $query->where('status', '!=', AppointmentStatus::CANCELLED);
    }

    public function scopeAwaitingConfirmation(Builder $query): Builder
    {
        return $query->where('status', AppointmentStatus::PENDING);
    }

    /**
     * Scope for optimized listing queries - select only essential columns.
     */
    public function scopeForListing(Builder $query): Builder
    {
        return $query->select([
            'id',
            'user_id',
            'appointment_date',
            'appointment_time',
            'status',
            'notes',
            'admin_notes',
            'created_at',
        ]);
    }

    /**
     * Scope to include common relations for listings.
     */
    public function scopeWithCommonRelations(Builder $query): Builder
    {
        return $query->with(['user', 'payment']);
    }

    /**
     * Scope to include patient with profile.
     */
    public function scopeWithPatientProfile(Builder $query): Builder
    {
        return $query->with(['user.profile']);
    }

    /**
     * Scope for full detail view with all relations.
     */
    public function scopeWithFullDetails(Builder $query): Builder
    {
        return $query->with(['user.profile', 'payment', 'medicalRecord.prescriptions']);
    }

    // ==================== Accessors ====================

    public function getFormattedDateAttribute(): string
    {
        return $this->appointment_date->format('Y-m-d');
    }

    public function getFormattedTimeAttribute(): string
    {
        return Carbon::parse($this->appointment_time)->format('H:i');
    }

    public function getDatetimeAttribute(): Carbon
    {
        return $this->appointment_date->copy()->setTimeFromTimeString(
            Carbon::parse($this->appointment_time)->format('H:i:s')
        );
    }

    public function getDayNameAttribute(): string
    {
        return DayOfWeek::fromDate($this->appointment_date)->labelAr();
    }

    public function getDayNameEnAttribute(): string
    {
        return DayOfWeek::fromDate($this->appointment_date)->label();
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status->labelAr();
    }

    public function getStatusLabelEnAttribute(): string
    {
        return $this->status->label();
    }

    public function getCanCancelAttribute(): bool
    {
        return $this->status->isActive() && $this->datetime->isFuture();
    }

    public function getIsUpcomingAttribute(): bool
    {
        return $this->status->isActive() && $this->datetime->isFuture();
    }

    public function getIsPastAttribute(): bool
    {
        return $this->datetime->isPast();
    }

    public function getIsTodayAttribute(): bool
    {
        return $this->appointment_date->isToday();
    }

    // ==================== Methods ====================

    public function isPending(): bool
    {
        return $this->status === AppointmentStatus::PENDING;
    }

    public function isConfirmed(): bool
    {
        return $this->status === AppointmentStatus::CONFIRMED;
    }

    public function isCompleted(): bool
    {
        return $this->status === AppointmentStatus::COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === AppointmentStatus::CANCELLED;
    }

    public function isNoShow(): bool
    {
        return $this->status === AppointmentStatus::NO_SHOW;
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    public function isFinal(): bool
    {
        return $this->status->isFinal();
    }

    public function confirm(): self
    {
        $this->update([
            'status' => AppointmentStatus::CONFIRMED,
            'confirmed_at' => now(),
        ]);

        return $this;
    }

    public function complete(?string $adminNotes = null): self
    {
        $data = [
            'status' => AppointmentStatus::COMPLETED,
            'completed_at' => now(),
        ];

        if ($adminNotes !== null) {
            $data['admin_notes'] = $adminNotes;
        }

        $this->update($data);

        return $this;
    }

    public function cancel(string $reason, CancelledBy $cancelledBy): self
    {
        $this->update([
            'status' => AppointmentStatus::CANCELLED,
            'cancellation_reason' => $reason,
            'cancelled_by' => $cancelledBy,
            'cancelled_at' => now(),
        ]);

        return $this;
    }

    public function markNoShow(): self
    {
        $this->update([
            'status' => AppointmentStatus::NO_SHOW,
        ]);

        return $this;
    }

    // ==================== Static Methods ====================

    public static function isSlotBooked(Carbon|string $date, string $time): bool
    {
        $dateString = $date instanceof Carbon ? $date->toDateString() : $date;
        // Normalize time to H:i format
        $normalizedTime = Carbon::parse($time)->format('H:i');

        return self::whereDate('appointment_date', $dateString)
            ->where(function ($query) use ($normalizedTime) {
                // Match time regardless of seconds
                $query->whereRaw("strftime('%H:%M', appointment_time) = ?", [$normalizedTime])
                    ->orWhere('appointment_time', $normalizedTime)
                    ->orWhere('appointment_time', $normalizedTime . ':00');
            })
            ->active()
            ->exists();
    }

    public static function getNoShowCountForPatient(int $patientId, int $days = 30): int
    {
        return self::forPatient($patientId)
            ->noShow()
            ->where('appointment_date', '>=', now()->subDays($days)->toDateString())
            ->count();
    }
}
