<?php

namespace Tests\Unit\Enums;

use App\Enums\Gender;
use PHPUnit\Framework\TestCase;

class GenderTest extends TestCase
{
    /** @test */
    public function enum_has_correct_values(): void
    {
        $this->assertEquals('male', Gender::MALE->value);
        $this->assertEquals('female', Gender::FEMALE->value);
    }

    /** @test */
    public function enum_has_correct_labels(): void
    {
        $this->assertEquals('Male', Gender::MALE->label());
        $this->assertEquals('Female', Gender::FEMALE->label());
    }

    /** @test */
    public function enum_has_correct_arabic_labels(): void
    {
        $this->assertEquals('ذكر', Gender::MALE->labelAr());
        $this->assertEquals('أنثى', Gender::FEMALE->labelAr());
    }

    /** @test */
    public function enum_values_returns_all_values(): void
    {
        $values = Gender::values();

        $this->assertCount(2, $values);
        $this->assertContains('male', $values);
        $this->assertContains('female', $values);
    }

    /** @test */
    public function enum_cases_returns_all_cases(): void
    {
        $cases = Gender::cases();

        $this->assertCount(2, $cases);
    }
}
