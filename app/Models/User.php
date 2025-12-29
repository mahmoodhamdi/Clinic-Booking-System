<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\UserRole;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'date_of_birth',
        'gender',
        'address',
        'avatar',
        'is_active',
        'phone_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'date_of_birth' => 'date',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'role' => UserRole::class,
            'gender' => Gender::class,
        ];
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    /**
     * Check if user is a secretary.
     */
    public function isSecretary(): bool
    {
        return $this->role === UserRole::SECRETARY;
    }

    /**
     * Check if user is a patient.
     */
    public function isPatient(): bool
    {
        return $this->role === UserRole::PATIENT;
    }

    /**
     * Check if user is staff (admin or secretary).
     */
    public function isStaff(): bool
    {
        return $this->isAdmin() || $this->isSecretary();
    }

    /**
     * Scope a query to only include active users.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include patients.
     */
    public function scopePatients($query)
    {
        return $query->where('role', UserRole::PATIENT);
    }

    /**
     * Scope a query to only include staff (admin and secretary).
     */
    public function scopeStaff($query)
    {
        return $query->whereIn('role', [UserRole::ADMIN, UserRole::SECRETARY]);
    }

    /**
     * Scope a query to only include verified users.
     */
    public function scopeVerified(Builder $query): Builder
    {
        return $query->whereNotNull('phone_verified_at');
    }

    /**
     * Get the avatar URL.
     */
    public function getAvatarUrlAttribute(): ?string
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }

        return null;
    }

    /**
     * Get the user's age.
     */
    public function getAgeAttribute(): ?int
    {
        if ($this->date_of_birth) {
            return $this->date_of_birth->age;
        }

        return null;
    }

    // ==================== Relationships ====================

    /**
     * Get the patient profile.
     */
    public function profile(): HasOne
    {
        return $this->hasOne(PatientProfile::class);
    }

    /**
     * Get the patient's appointments.
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    // ==================== Patient Statistics ====================
    // Note: Statistics accessors have been moved to PatientStatisticsService
    // to avoid N+1 query problems. Use the service for batch statistics.

    /**
     * Check if patient has complete profile.
     */
    public function getHasCompleteProfileAttribute(): bool
    {
        return $this->profile !== null && $this->profile->is_complete;
    }
}
