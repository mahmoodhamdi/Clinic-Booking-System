<?php

namespace Tests\Feature\Api\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'phone' => '01012345678',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'phone' => '01012345678',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['user', 'token']
            ])
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function login_fails_with_wrong_password(): void
    {
        User::factory()->create([
            'phone' => '01012345678',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'phone' => '01012345678',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function login_fails_with_nonexistent_phone(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'phone' => '01099999999',
            'password' => 'password123',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function inactive_user_cannot_login(): void
    {
        User::factory()->inactive()->create([
            'phone' => '01012345678',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'phone' => '01012345678',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function login_returns_token(): void
    {
        User::factory()->create([
            'phone' => '01012345678',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'phone' => '01012345678',
            'password' => 'password123',
        ]);

        $response->assertOk();
        $this->assertNotNull($response->json('data.token'));
    }

    /** @test */
    public function login_requires_phone(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    /** @test */
    public function login_requires_password(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'phone' => '01012345678',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }
}
