<?php

namespace Tests\Unit\Enums;

use App\Enums\UserRole;
use PHPUnit\Framework\TestCase;

class UserRoleTest extends TestCase
{
    /** @test */
    public function enum_has_correct_values(): void
    {
        $this->assertEquals('admin', UserRole::ADMIN->value);
        $this->assertEquals('secretary', UserRole::SECRETARY->value);
        $this->assertEquals('patient', UserRole::PATIENT->value);
    }

    /** @test */
    public function enum_has_correct_labels(): void
    {
        $this->assertEquals('Admin', UserRole::ADMIN->label());
        $this->assertEquals('Secretary', UserRole::SECRETARY->label());
        $this->assertEquals('Patient', UserRole::PATIENT->label());
    }

    /** @test */
    public function enum_has_correct_arabic_labels(): void
    {
        $this->assertEquals('مدير', UserRole::ADMIN->labelAr());
        $this->assertEquals('سكرتير', UserRole::SECRETARY->labelAr());
        $this->assertEquals('مريض', UserRole::PATIENT->labelAr());
    }

    /** @test */
    public function enum_values_returns_all_values(): void
    {
        $values = UserRole::values();

        $this->assertCount(3, $values);
        $this->assertContains('admin', $values);
        $this->assertContains('secretary', $values);
        $this->assertContains('patient', $values);
    }

    /** @test */
    public function enum_cases_returns_all_cases(): void
    {
        $cases = UserRole::cases();

        $this->assertCount(3, $cases);
    }
}
