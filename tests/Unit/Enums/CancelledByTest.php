<?php

namespace Tests\Unit\Enums;

use App\Enums\CancelledBy;
use PHPUnit\Framework\TestCase;

class CancelledByTest extends TestCase
{
    /** @test */
    public function has_correct_values(): void
    {
        $this->assertEquals('patient', CancelledBy::PATIENT->value);
        $this->assertEquals('admin', CancelledBy::ADMIN->value);
    }

    /** @test */
    public function has_english_labels(): void
    {
        $this->assertEquals('Patient', CancelledBy::PATIENT->label());
        $this->assertEquals('Admin', CancelledBy::ADMIN->label());
    }

    /** @test */
    public function has_arabic_labels(): void
    {
        $this->assertEquals('المريض', CancelledBy::PATIENT->labelAr());
        $this->assertEquals('الإدارة', CancelledBy::ADMIN->labelAr());
    }
}
