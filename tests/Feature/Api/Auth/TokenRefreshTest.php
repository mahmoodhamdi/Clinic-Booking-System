<?php

namespace Tests\Feature\Api\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TokenRefreshTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_refresh_token_with_valid_token(): void
    {
        $user = User::factory()->patient()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/auth/refresh');

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['token'],
            ]);

        // Verify new token is returned
        $this->assertNotNull($response->json('data.token'));
        $this->assertIsString($response->json('data.token'));
    }

    /** @test */
    public function refresh_returns_valid_token_string(): void
    {
        $user = User::factory()->patient()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/auth/refresh');

        $response->assertOk();
        $token = $response->json('data.token');

        // Token should be a non-empty string
        $this->assertIsString($token);
        $this->assertNotEmpty($token);
        $this->assertTrue(strlen($token) > 20); // Sanctum tokens are long
    }

    /** @test */
    public function refresh_without_authentication_returns_401(): void
    {
        $response = $this->postJson('/api/auth/refresh');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function admin_can_refresh_token(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/auth/refresh');

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertNotNull($response->json('data.token'));
    }

    /** @test */
    public function secretary_can_refresh_token(): void
    {
        $secretary = User::factory()->secretary()->create();
        Sanctum::actingAs($secretary);

        $response = $this->postJson('/api/auth/refresh');

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertNotNull($response->json('data.token'));
    }

    /** @test */
    public function refreshed_token_can_be_used_for_authenticated_requests(): void
    {
        $patient = User::factory()->patient()->create();
        Sanctum::actingAs($patient);

        // Refresh token
        $response = $this->postJson('/api/auth/refresh');
        $response->assertOk();

        $newToken = $response->json('data.token');

        // Create a new request with the new token
        $headers = ['Authorization' => "Bearer {$newToken}"];

        // Should be able to access authenticated endpoint with new token
        $meResponse = $this->getJson('/api/auth/me', $headers);

        $meResponse->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $patient->id,
                ],
            ]);
    }

    /** @test */
    public function patient_can_refresh_token(): void
    {
        $patient = User::factory()->patient()->create();
        Sanctum::actingAs($patient);

        $response = $this->postJson('/api/auth/refresh');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'تم تحديث التوكن بنجاح.',
            ]);
    }
}
