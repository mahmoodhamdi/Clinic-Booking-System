<?php

namespace Tests\Feature\Middleware;

use App\Models\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddRequestIdTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a schedule so the API returns valid data
        Schedule::factory()->create();
    }

    /**
     * @test
     */
    public function response_includes_request_id_header(): void
    {
        $response = $this->getJson('/api/slots/dates');

        $response->assertHeader('X-Request-ID');
    }

    /**
     * @test
     */
    public function request_id_is_uuid_format(): void
    {
        $response = $this->getJson('/api/slots/dates');

        $requestId = $response->headers->get('X-Request-ID');
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $requestId
        );
    }

    /**
     * @test
     */
    public function uses_provided_request_id_from_header(): void
    {
        $customRequestId = 'custom-request-id-12345';

        $response = $this->withHeader('X-Request-ID', $customRequestId)
            ->getJson('/api/slots/dates');

        $response->assertHeader('X-Request-ID', $customRequestId);
    }

    /**
     * @test
     */
    public function different_requests_get_different_ids(): void
    {
        $response1 = $this->getJson('/api/slots/dates');
        $response2 = $this->getJson('/api/slots/dates');

        $requestId1 = $response1->headers->get('X-Request-ID');
        $requestId2 = $response2->headers->get('X-Request-ID');

        $this->assertNotEquals($requestId1, $requestId2);
    }

    /**
     * @test
     */
    public function request_id_is_present_on_post_requests(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'phone' => '01234567890',
            'password' => 'password123',
        ]);

        $response->assertHeader('X-Request-ID');
    }

    /**
     * @test
     */
    public function request_id_is_present_on_validation_error_responses(): void
    {
        $response = $this->postJson('/api/auth/login', []);

        $response->assertHeader('X-Request-ID');
    }
}
