<?php

namespace Tests\Unit\Exceptions;

use App\Exceptions\SlotNotAvailableException;
use Tests\TestCase;

class SlotNotAvailableExceptionTest extends TestCase
{
    /** @test */
    public function default_reason_is_slot_taken(): void
    {
        $e = new SlotNotAvailableException('2026-05-08', '10:00');

        $this->assertSame('SLOT_NOT_AVAILABLE', $e->getErrorCode());
        $this->assertSame(422, $e->getCode());
        $this->assertSame([
            'date' => '2026-05-08',
            'time' => '10:00',
            'reason' => 'slot_taken',
        ], $e->getContext());
    }

    /** @test */
    public function each_reason_produces_a_non_empty_message(): void
    {
        foreach (['slot_taken', 'vacation', 'outside_hours', 'past_time', 'made_up_reason'] as $reason) {
            $e = new SlotNotAvailableException('2026-05-08', '10:00', $reason);
            $this->assertNotSame('', $e->getMessage(), "reason=$reason produced empty message");
            $this->assertSame($reason, $e->getContext()['reason']);
        }
    }

    /** @test */
    public function date_and_time_are_passed_through_to_context(): void
    {
        $e = new SlotNotAvailableException('2026-12-25', '15:30', 'vacation');

        $this->assertSame('2026-12-25', $e->getContext()['date']);
        $this->assertSame('15:30', $e->getContext()['time']);
    }
}
