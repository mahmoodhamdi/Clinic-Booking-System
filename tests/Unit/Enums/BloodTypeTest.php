<?php

namespace Tests\Unit\Enums;

use App\Enums\BloodType;
use PHPUnit\Framework\TestCase;

class BloodTypeTest extends TestCase
{
    /** @test */
    public function has_correct_values(): void
    {
        $this->assertEquals('A+', BloodType::A_POSITIVE->value);
        $this->assertEquals('A-', BloodType::A_NEGATIVE->value);
        $this->assertEquals('B+', BloodType::B_POSITIVE->value);
        $this->assertEquals('B-', BloodType::B_NEGATIVE->value);
        $this->assertEquals('AB+', BloodType::AB_POSITIVE->value);
        $this->assertEquals('AB-', BloodType::AB_NEGATIVE->value);
        $this->assertEquals('O+', BloodType::O_POSITIVE->value);
        $this->assertEquals('O-', BloodType::O_NEGATIVE->value);
    }

    /** @test */
    public function has_english_labels(): void
    {
        $this->assertEquals('A+', BloodType::A_POSITIVE->label());
        $this->assertEquals('O-', BloodType::O_NEGATIVE->label());
    }

    /** @test */
    public function has_arabic_labels(): void
    {
        $this->assertEquals('A موجب', BloodType::A_POSITIVE->labelAr());
        $this->assertEquals('A سالب', BloodType::A_NEGATIVE->labelAr());
        $this->assertEquals('B موجب', BloodType::B_POSITIVE->labelAr());
        $this->assertEquals('O سالب', BloodType::O_NEGATIVE->labelAr());
    }

    /** @test */
    public function values_returns_all_values(): void
    {
        $values = BloodType::values();

        $this->assertCount(8, $values);
        $this->assertContains('A+', $values);
        $this->assertContains('O-', $values);
    }
}
