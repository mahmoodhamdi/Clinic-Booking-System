<?php

namespace App\Exceptions;

class SlotNotAvailableException extends BusinessLogicException
{
    public function __construct(
        string $date,
        string $time,
        string $reason = 'slot_taken'
    ) {
        $message = match ($reason) {
            'slot_taken' => __('validation.slot_already_booked'),
            'vacation' => __('validation.slot_on_vacation'),
            'outside_hours' => __('validation.slot_outside_working_hours'),
            'past_time' => __('validation.slot_in_past'),
            default => __('validation.slot_not_available'),
        };

        parent::__construct(
            message: $message,
            errorCode: 'SLOT_NOT_AVAILABLE',
            context: [
                'date' => $date,
                'time' => $time,
                'reason' => $reason,
            ],
            httpCode: 422
        );
    }
}
