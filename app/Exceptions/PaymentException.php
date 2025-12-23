<?php

namespace App\Exceptions;

class PaymentException extends BusinessLogicException
{
    public function __construct(
        string $reason,
        ?int $appointmentId = null,
        ?float $amount = null
    ) {
        $message = match ($reason) {
            'already_paid' => __('validation.appointment_already_paid'),
            'invalid_amount' => __('validation.invalid_payment_amount'),
            'appointment_cancelled' => __('validation.cannot_pay_cancelled_appointment'),
            'refund_failed' => __('validation.refund_failed'),
            default => __('validation.payment_error'),
        };

        parent::__construct(
            message: $message,
            errorCode: 'PAYMENT_ERROR',
            context: array_filter([
                'reason' => $reason,
                'appointment_id' => $appointmentId,
                'amount' => $amount,
            ]),
            httpCode: 422
        );
    }
}
