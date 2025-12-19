<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case NO_SHOW = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::CONFIRMED => 'Confirmed',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
            self::NO_SHOW => 'No Show',
        };
    }

    public function labelAr(): string
    {
        return match ($this) {
            self::PENDING => 'في الانتظار',
            self::CONFIRMED => 'مؤكد',
            self::COMPLETED => 'مكتمل',
            self::CANCELLED => 'ملغي',
            self::NO_SHOW => 'لم يحضر',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::CONFIRMED => 'info',
            self::COMPLETED => 'success',
            self::CANCELLED => 'danger',
            self::NO_SHOW => 'secondary',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::PENDING, self::CONFIRMED]);
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::COMPLETED, self::CANCELLED, self::NO_SHOW]);
    }

    public static function activeStatuses(): array
    {
        return [self::PENDING, self::CONFIRMED];
    }

    public static function finalStatuses(): array
    {
        return [self::COMPLETED, self::CANCELLED, self::NO_SHOW];
    }
}
