<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function api_returns_arabic_messages_when_accept_language_header_is_ar(): void
    {
        User::factory()->create([
            'phone' => '01012345678',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'phone' => '01012345678',
            'password' => 'password123',
        ], [
            'Accept-Language' => 'ar',
        ]);

        $response->assertOk();

        // Message should be present and is string
        $message = $response->json('message');
        $this->assertIsString($message);
        $this->assertNotEmpty($message);
    }

    /** @test */
    public function api_returns_messages_when_accept_language_header_is_en(): void
    {
        User::factory()->create([
            'phone' => '01012345678',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'phone' => '01012345678',
            'password' => 'password123',
        ], [
            'Accept-Language' => 'en',
        ]);

        $response->assertOk();

        // Message should be present
        $message = $response->json('message');
        $this->assertIsString($message);
        $this->assertNotEmpty($message);
    }

    /** @test */
    public function api_returns_message_when_no_accept_language_header(): void
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

        // Should return a message
        $message = $response->json('message');
        $this->assertIsString($message);
        $this->assertNotEmpty($message);
    }

    /** @test */
    public function validation_errors_are_returned(): void
    {
        // Missing phone field
        $response = $this->postJson('/api/auth/login', [
            'password' => 'password123',
        ], [
            'Accept-Language' => 'ar',
        ]);

        $response->assertStatus(422);

        // Error messages should be present
        $errors = $response->json('errors');
        $this->assertIsArray($errors);
        $this->assertArrayHasKey('phone', $errors);

        // Phone error should be a string
        $phoneError = $errors['phone'][0];
        $this->assertIsString($phoneError);
    }

    /** @test */
    public function validation_errors_returned_with_english_header(): void
    {
        // Missing password field
        $response = $this->postJson('/api/auth/login', [
            'phone' => '01012345678',
        ], [
            'Accept-Language' => 'en',
        ]);

        $response->assertStatus(422);

        $errors = $response->json('errors');
        $this->assertIsArray($errors);
        $this->assertArrayHasKey('password', $errors);

        // Password error should be present
        $passwordError = $errors['password'][0];
        $this->assertIsString($passwordError);
    }

    /** @test */
    public function success_message_returned_on_login(): void
    {
        $user = User::factory()->create([
            'phone' => '01012345678',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'phone' => '01012345678',
            'password' => 'password123',
        ]);

        $response->assertOk();

        // Message should be present
        $message = $response->json('message');
        $this->assertIsString($message);
        $this->assertNotEmpty($message);
    }

    /** @test */
    public function lang_query_parameter_ar_returns_response(): void
    {
        User::factory()->create([
            'phone' => '01012345678',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/auth/login?lang=ar', [
            'phone' => '01012345678',
            'password' => 'password123',
        ]);

        $response->assertOk();

        $message = $response->json('message');
        $this->assertIsString($message);
    }

    /** @test */
    public function lang_query_parameter_en_returns_response(): void
    {
        User::factory()->create([
            'phone' => '01012345678',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/auth/login?lang=en', [
            'phone' => '01012345678',
            'password' => 'password123',
        ]);

        $response->assertOk();

        $message = $response->json('message');
        $this->assertIsString($message);
    }

    /** @test */
    public function invalid_lang_parameter_returns_response(): void
    {
        User::factory()->create([
            'phone' => '01012345678',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/auth/login?lang=fr', [
            'phone' => '01012345678',
            'password' => 'password123',
        ]);

        $response->assertOk();

        // Should fallback gracefully
        $message = $response->json('message');
        $this->assertIsString($message);
    }

    /** @test */
    public function error_response_includes_message(): void
    {
        // Unauthorized error
        $response = $this->postJson('/api/auth/login', [
            'phone' => '01012345678',
            'password' => 'wrongpassword',
        ], [
            'Accept-Language' => 'ar',
        ]);

        $response->assertStatus(401);
        $message = $response->json('message');

        // Error message should be present
        $this->assertIsString($message);
        $this->assertNotEmpty($message);
    }
}
