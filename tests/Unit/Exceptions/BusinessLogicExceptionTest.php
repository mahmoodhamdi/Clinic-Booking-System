<?php

namespace Tests\Unit\Exceptions;

use App\Exceptions\BusinessLogicException;
use Tests\TestCase;

class BusinessLogicExceptionTest extends TestCase
{
    /** @test */
    public function default_constructor_uses_business_error_code_and_422(): void
    {
        $e = new BusinessLogicException('something went wrong');

        $this->assertSame('something went wrong', $e->getMessage());
        $this->assertSame('BUSINESS_ERROR', $e->getErrorCode());
        $this->assertSame([], $e->getContext());
        $this->assertSame(422, $e->getCode());
    }

    /** @test */
    public function constructor_accepts_custom_error_code_context_and_status(): void
    {
        $e = new BusinessLogicException(
            'too many no-shows',
            'TOO_MANY_NO_SHOWS',
            ['no_show_count' => 5, 'max_allowed' => 3],
            429
        );

        $this->assertSame('TOO_MANY_NO_SHOWS', $e->getErrorCode());
        $this->assertSame(['no_show_count' => 5, 'max_allowed' => 3], $e->getContext());
        $this->assertSame(429, $e->getCode());
    }

    /** @test */
    public function render_returns_json_with_message_code_and_context(): void
    {
        $e = new BusinessLogicException(
            'duplicate booking',
            'DUPLICATE_BOOKING',
            ['patient_id' => 1, 'date' => '2026-05-08'],
            422
        );

        $response = $e->render();
        $body = $response->getData(true);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertFalse($body['success']);
        $this->assertSame('duplicate booking', $body['message']);
        $this->assertSame('DUPLICATE_BOOKING', $body['error_code']);
        $this->assertSame(['patient_id' => 1, 'date' => '2026-05-08'], $body['context']);
    }
}
