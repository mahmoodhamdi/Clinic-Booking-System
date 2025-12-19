<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case REFUNDED = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::PAID => 'Paid',
            self::REFUNDED => 'Refunded',
        };
    }

    public function labelAr(): string
    {
        return match ($this) {
            self::PENDING => 'معلق',
            self::PAID => 'مدفوع',
            self::REFUNDED => 'مسترد',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::PAID => 'success',
            self::REFUNDED => 'danger',
        };
    }

    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    public function isPaid(): bool
    {
        return $this === self::PAID;
    }

    public function isRefunded(): bool
    {
        return $this === self::REFUNDED;
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
