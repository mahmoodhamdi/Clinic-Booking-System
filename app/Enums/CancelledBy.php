<?php

namespace App\Enums;

enum CancelledBy: string
{
    case PATIENT = 'patient';
    case ADMIN = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::PATIENT => 'Patient',
            self::ADMIN => 'Admin',
        };
    }

    public function labelAr(): string
    {
        return match ($this) {
            self::PATIENT => 'المريض',
            self::ADMIN => 'الإدارة',
        };
    }
}
