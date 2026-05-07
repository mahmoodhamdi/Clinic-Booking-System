<?php

namespace Tests\Unit\Enums;

use App\Enums\PaymentStatus;
use Tests\TestCase;

class PaymentStatusTest extends TestCase
{
    /** @test */
    public function has_three_cases_with_expected_string_values(): void
    {
        $this->assertSame('pending', PaymentStatus::PENDING->value);
        $this->assertSame('paid', PaymentStatus::PAID->value);
        $this->assertSame('refunded', PaymentStatus::REFUNDED->value);
        $this->assertCount(3, PaymentStatus::cases());
    }

    /** @test */
    public function each_case_has_an_english_label(): void
    {
        $this->assertSame('Pending', PaymentStatus::PENDING->label());
        $this->assertSame('Paid', PaymentStatus::PAID->label());
        $this->assertSame('Refunded', PaymentStatus::REFUNDED->label());
    }

    /** @test */
    public function each_case_has_an_arabic_label(): void
    {
        $this->assertSame('معلق', PaymentStatus::PENDING->labelAr());
        $this->assertSame('مدفوع', PaymentStatus::PAID->labelAr());
        $this->assertSame('مسترد', PaymentStatus::REFUNDED->labelAr());
    }

    /** @test */
    public function each_case_has_a_color_identifier(): void
    {
        $this->assertSame('warning', PaymentStatus::PENDING->color());
        $this->assertSame('success', PaymentStatus::PAID->color());
        $this->assertSame('danger', PaymentStatus::REFUNDED->color());
    }

    /** @test */
    public function status_predicates_match_each_case(): void
    {
        $this->assertTrue(PaymentStatus::PENDING->isPending());
        $this->assertFalse(PaymentStatus::PAID->isPending());
        $this->assertFalse(PaymentStatus::REFUNDED->isPending());

        $this->assertTrue(PaymentStatus::PAID->isPaid());
        $this->assertFalse(PaymentStatus::PENDING->isPaid());
        $this->assertFalse(PaymentStatus::REFUNDED->isPaid());

        $this->assertTrue(PaymentStatus::REFUNDED->isRefunded());
        $this->assertFalse(PaymentStatus::PENDING->isRefunded());
        $this->assertFalse(PaymentStatus::PAID->isRefunded());
    }

    /** @test */
    public function values_returns_all_string_values(): void
    {
        $this->assertSame(['pending', 'paid', 'refunded'], PaymentStatus::values());
    }
}
