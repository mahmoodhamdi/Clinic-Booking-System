<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function login_rate_limiting(): void
    {
        // Auth endpoints allow 5 requests per minute
        // Send 6 requests to trigger rate limiting
        for ($i = 0; $i < 6; $i++) {
            $response = $this->postJson('/api/auth/login', [
                'phone' => '01012345678',
                'password' => 'wrong',
            ]);

            if ($i < 5) {
                // First 5 should get through (validation error, not rate limited)
                $this->assertNotEquals(429, $response->status());
            }
        }

        // 6th should be rate limited
        $response->assertStatus(429);
    }

    /** @test */
    public function registration_rate_limiting(): void
    {
        // Auth endpoints allow 5 requests per minute
        for ($i = 0; $i < 6; $i++) {
            $response = $this->postJson('/api/auth/register', [
                'name' => 'Test User',
                'phone' => "0101234567{$i}",
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ]);
        }

        // 6th should be rate limited
        $response->assertStatus(429);
    }

    /** @test */
    public function slots_rate_limiting(): void
    {
        // Slots allow 20 requests per minute
        for ($i = 0; $i < 21; $i++) {
            $response = $this->getJson('/api/slots/dates');
        }

        // 21st should be rate limited
        $response->assertStatus(429);
    }

    /** @test */
    public function booking_rate_limiting(): void
    {
        $patient = User::factory()->patient()->create();

        // Booking allows 3 requests per minute
        for ($i = 0; $i < 4; $i++) {
            $response = $this->actingAs($patient)
                ->postJson('/api/appointments', [
                    'date' => now()->addDays(1)->format('Y-m-d'),
                    'time' => '10:00',
                ]);
        }

        // 4th should be rate limited
        $response->assertStatus(429);
    }

    /** @test */
    public function authenticated_api_rate_limiting(): void
    {
        $patient = User::factory()->patient()->create();

        // API allows 60 requests per minute
        for ($i = 0; $i < 61; $i++) {
            $response = $this->actingAs($patient)
                ->getJson('/api/patient/dashboard');
        }

        // 61st should be rate limited
        $response->assertStatus(429);
    }

    /** @test */
    public function rate_limit_returns_proper_response(): void
    {
        // Exhaust auth rate limit
        for ($i = 0; $i < 6; $i++) {
            $response = $this->postJson('/api/auth/login', [
                'phone' => '01012345678',
                'password' => 'wrong',
            ]);
        }

        // Should be rate limited with 429 status
        $response->assertStatus(429);
    }
}
