<?php

namespace App\Enums;

enum BloodType: string
{
    case A_POSITIVE = 'A+';
    case A_NEGATIVE = 'A-';
    case B_POSITIVE = 'B+';
    case B_NEGATIVE = 'B-';
    case AB_POSITIVE = 'AB+';
    case AB_NEGATIVE = 'AB-';
    case O_POSITIVE = 'O+';
    case O_NEGATIVE = 'O-';

    public function label(): string
    {
        return $this->value;
    }

    public function labelAr(): string
    {
        return match ($this) {
            self::A_POSITIVE => 'A موجب',
            self::A_NEGATIVE => 'A سالب',
            self::B_POSITIVE => 'B موجب',
            self::B_NEGATIVE => 'B سالب',
            self::AB_POSITIVE => 'AB موجب',
            self::AB_NEGATIVE => 'AB سالب',
            self::O_POSITIVE => 'O موجب',
            self::O_NEGATIVE => 'O سالب',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
