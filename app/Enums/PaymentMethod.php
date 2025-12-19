<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case CASH = 'cash';
    case CARD = 'card';
    case WALLET = 'wallet';

    public function label(): string
    {
        return match ($this) {
            self::CASH => 'Cash',
            self::CARD => 'Card',
            self::WALLET => 'Wallet',
        };
    }

    public function labelAr(): string
    {
        return match ($this) {
            self::CASH => 'نقداً',
            self::CARD => 'بطاقة',
            self::WALLET => 'محفظة',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::CASH => 'cash',
            self::CARD => 'credit-card',
            self::WALLET => 'wallet',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
