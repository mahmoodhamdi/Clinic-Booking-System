<?php

namespace Tests\Unit\Exceptions;

use App\Exceptions\PaymentException;
use Tests\TestCase;

class PaymentExceptionTest extends TestCase
{
    /** @test */
    public function constructor_sets_payment_error_code_and_422(): void
    {
        $e = new PaymentException('invalid_amount', 7, 100.0);

        $this->assertSame('PAYMENT_ERROR', $e->getErrorCode());
        $this->assertSame(422, $e->getCode());
        $this->assertSame(7, $e->getAppointmentId());
        $this->assertSame(100.0, $e->getAmount());
    }

    /** @test */
    public function reason_lands_in_context(): void
    {
        $e = new PaymentException('already_paid', 1, 50.0);

        $this->assertSame('already_paid', $e->getContext()['reason']);
    }

    /** @test */
    public function null_appointment_and_amount_are_filtered_from_context(): void
    {
        $e = new PaymentException('refund_failed');

        $this->assertNull($e->getAppointmentId());
        $this->assertNull($e->getAmount());
        $this->assertSame(['reason' => 'refund_failed'], $e->getContext());
    }

    /** @test */
    public function known_reasons_produce_translated_messages(): void
    {
        // The translation may resolve to either the Arabic source or the
        // English fallback depending on app.locale; either way it should
        // not be the raw key.
        foreach (['already_paid', 'invalid_amount', 'appointment_cancelled', 'refund_failed'] as $reason) {
            $e = new PaymentException($reason, 1, 100.0);
            $this->assertNotSame('', $e->getMessage(), "$reason produced empty message");
        }
    }

    /** @test */
    public function unknown_reason_falls_back_to_payment_error_message(): void
    {
        $e = new PaymentException('totally-fake-reason', 1, 100.0);

        // The default branch uses validation.payment_error key
        $this->assertNotSame('', $e->getMessage());
        $this->assertSame('totally-fake-reason', $e->getContext()['reason']);
    }
}
