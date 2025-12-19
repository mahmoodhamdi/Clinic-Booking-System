<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case SECRETARY = 'secretary';
    case PATIENT = 'patient';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Admin',
            self::SECRETARY => 'Secretary',
            self::PATIENT => 'Patient',
        };
    }

    public function labelAr(): string
    {
        return match ($this) {
            self::ADMIN => 'مدير',
            self::SECRETARY => 'سكرتير',
            self::PATIENT => 'مريض',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
