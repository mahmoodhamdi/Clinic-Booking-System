<?php

namespace Tests\Feature\Api\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_register_with_valid_data(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Ahmed Mohamed',
            'phone' => '01012345678',
            'email' => 'ahmed@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['user', 'token']
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('users', [
            'phone' => '01012345678',
            'email' => 'ahmed@example.com',
        ]);
    }

    /** @test */
    public function registration_requires_name(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'phone' => '01012345678',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function registration_requires_phone(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Ahmed',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    /** @test */
    public function registration_requires_valid_phone(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Ahmed',
            'phone' => 'invalid',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    /** @test */
    public function registration_requires_unique_phone(): void
    {
        User::factory()->create(['phone' => '01012345678']);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Ahmed',
            'phone' => '01012345678',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    /** @test */
    public function registration_requires_password_confirmation(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Ahmed',
            'phone' => '01012345678',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function registration_fails_with_weak_password(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Ahmed',
            'phone' => '01012345678',
            'password' => '123',
            'password_confirmation' => '123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function registered_user_receives_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Ahmed',
            'phone' => '01012345678',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
        ]);

        $response->assertStatus(201);
        $this->assertNotNull($response->json('data.token'));
    }

    /** @test */
    public function email_is_optional(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Ahmed',
            'phone' => '01012345678',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
        ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function email_must_be_unique_if_provided(): void
    {
        User::factory()->create(['email' => 'ahmed@example.com']);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Ahmed',
            'phone' => '01012345678',
            'email' => 'ahmed@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}
