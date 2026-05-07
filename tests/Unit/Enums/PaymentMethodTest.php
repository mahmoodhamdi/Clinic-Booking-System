<?php

namespace Tests\Unit\Enums;

use App\Enums\PaymentMethod;
use Tests\TestCase;

class PaymentMethodTest extends TestCase
{
    /** @test */
    public function has_three_cases_with_expected_string_values(): void
    {
        $this->assertSame('cash', PaymentMethod::CASH->value);
        $this->assertSame('card', PaymentMethod::CARD->value);
        $this->assertSame('wallet', PaymentMethod::WALLET->value);
        $this->assertCount(3, PaymentMethod::cases());
    }

    /** @test */
    public function each_case_has_an_english_label(): void
    {
        $this->assertSame('Cash', PaymentMethod::CASH->label());
        $this->assertSame('Card', PaymentMethod::CARD->label());
        $this->assertSame('Wallet', PaymentMethod::WALLET->label());
    }

    /** @test */
    public function each_case_has_an_arabic_label(): void
    {
        $this->assertSame('نقداً', PaymentMethod::CASH->labelAr());
        $this->assertSame('بطاقة', PaymentMethod::CARD->labelAr());
        $this->assertSame('محفظة', PaymentMethod::WALLET->labelAr());
    }

    /** @test */
    public function each_case_has_an_icon_identifier(): void
    {
        $this->assertSame('cash', PaymentMethod::CASH->icon());
        $this->assertSame('credit-card', PaymentMethod::CARD->icon());
        $this->assertSame('wallet', PaymentMethod::WALLET->icon());
    }

    /** @test */
    public function values_returns_all_string_values(): void
    {
        $this->assertSame(['cash', 'card', 'wallet'], PaymentMethod::values());
    }
}
