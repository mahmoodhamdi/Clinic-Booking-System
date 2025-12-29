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

    /** @test */
    public function login_validates_egyptian_phone_format(): void
    {
        // Invalid phone formats
        $invalidPhones = [
            '12345678901',      // Not starting with 01
            '0101234567',       // Too short
            '010123456789',     // Too long
            '01312345678',      // Invalid second digit
            'abcdefghijk',      // Not numeric
        ];

        foreach ($invalidPhones as $phone) {
            $response = $this->postJson('/api/auth/login', [
                'phone' => $phone,
                'password' => 'password123',
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['phone']);
        }
    }

    /** @test */
    public function login_accepts_valid_egyptian_phone_formats(): void
    {
        // Valid Egyptian phone formats (01[0125]xxxxxxxx)
        $validPhones = [
            '01012345678',  // Vodafone
            '01112345678',  // Etisalat
            '01212345678',  // Orange
            '01512345678',  // WE
        ];

        foreach ($validPhones as $phone) {
            User::factory()->create([
                'phone' => $phone,
                'password' => 'password123',
            ]);

            $response = $this->postJson('/api/auth/login', [
                'phone' => $phone,
                'password' => 'password123',
            ]);

            $response->assertOk();

            // Clean up for next iteration
            User::where('phone', $phone)->delete();
        }
    }

    /** @test */
    public function login_requires_minimum_password_length(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'phone' => '01012345678',
            'password' => '12345',  // 5 characters, less than minimum 6
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function login_strips_whitespace_from_phone(): void
    {
        User::factory()->create([
            'phone' => '01012345678',
            'password' => 'password123',
        ]);

        // Phone with spaces should be cleaned and work
        $response = $this->postJson('/api/auth/login', [
            'phone' => '010 1234 5678',
            'password' => 'password123',
        ]);

        $response->assertOk();
    }
}
