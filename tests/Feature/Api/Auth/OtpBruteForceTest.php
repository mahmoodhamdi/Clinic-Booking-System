<?php

namespace Tests\Feature\Api\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OtpBruteForceTest extends TestCase
{
    use RefreshDatabase;

    private const MAX_ATTEMPTS = 5;

    private string $phone = '01012345678';

    private string $correctOtp = '123456';

    protected function setUp(): void
    {
        parent::setUp();

        User::factory()->create(['phone' => $this->phone]);
    }

    /** @test */
    public function locks_after_five_failed_attempts(): void
    {
        DB::table('password_reset_tokens')->insert([
            'phone' => $this->phone,
            'token' => Hash::make($this->correctOtp),
            'attempts' => 0,
            'created_at' => now(),
        ]);

        // Attempt wrong OTP 5 times
        for ($i = 0; $i < self::MAX_ATTEMPTS; $i++) {
            $response = $this->postJson('/api/auth/verify-otp', [
                'phone' => $this->phone,
                'otp' => '000000',
            ]);

            if ($i < self::MAX_ATTEMPTS - 1) {
                $response->assertStatus(422);
            }
        }

        // 5th attempt returns 429 (locked)
        $this->assertDatabaseHas('password_reset_tokens', [
            'phone' => $this->phone,
            'attempts' => self::MAX_ATTEMPTS,
        ]);

        // 6th attempt should be locked
        $response = $this->postJson('/api/auth/verify-otp', [
            'phone' => $this->phone,
            'otp' => '000000',
        ]);

        $response->assertStatus(429);
    }

    /** @test */
    public function correct_otp_works_within_attempts(): void
    {
        DB::table('password_reset_tokens')->insert([
            'phone' => $this->phone,
            'token' => Hash::make($this->correctOtp),
            'attempts' => 0,
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/auth/verify-otp', [
            'phone' => $this->phone,
            'otp' => $this->correctOtp,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['verified' => true],
            ]);
    }

    /** @test */
    public function correct_otp_works_after_some_failed_attempts(): void
    {
        DB::table('password_reset_tokens')->insert([
            'phone' => $this->phone,
            'token' => Hash::make($this->correctOtp),
            'attempts' => 0,
            'created_at' => now(),
        ]);

        // 3 failed attempts
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/auth/verify-otp', [
                'phone' => $this->phone,
                'otp' => '000000',
            ]);
        }

        // Correct OTP should still work
        $response = $this->postJson('/api/auth/verify-otp', [
            'phone' => $this->phone,
            'otp' => $this->correctOtp,
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Attempts should be reset
        $this->assertDatabaseHas('password_reset_tokens', [
            'phone' => $this->phone,
            'attempts' => 0,
        ]);
    }

    /** @test */
    public function returns_remaining_attempts_on_failure(): void
    {
        DB::table('password_reset_tokens')->insert([
            'phone' => $this->phone,
            'token' => Hash::make($this->correctOtp),
            'attempts' => 0,
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/auth/verify-otp', [
            'phone' => $this->phone,
            'otp' => '000000',
        ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    /** @test */
    public function locked_account_cannot_verify_otp_even_with_correct_code(): void
    {
        DB::table('password_reset_tokens')->insert([
            'phone' => $this->phone,
            'token' => Hash::make($this->correctOtp),
            'attempts' => self::MAX_ATTEMPTS,
            'locked_until' => now()->addMinutes(30),
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/auth/verify-otp', [
            'phone' => $this->phone,
            'otp' => $this->correctOtp,
        ]);

        $response->assertStatus(429);
    }

    /** @test */
    public function lockout_expires_after_timeout(): void
    {
        DB::table('password_reset_tokens')->insert([
            'phone' => $this->phone,
            'token' => Hash::make($this->correctOtp),
            'attempts' => self::MAX_ATTEMPTS,
            'locked_until' => now()->subMinutes(1), // Expired lockout
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/auth/verify-otp', [
            'phone' => $this->phone,
            'otp' => $this->correctOtp,
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function reset_password_also_has_brute_force_protection(): void
    {
        DB::table('password_reset_tokens')->insert([
            'phone' => $this->phone,
            'token' => Hash::make($this->correctOtp),
            'attempts' => 0,
            'created_at' => now(),
        ]);

        // Attempt wrong OTP 5 times via reset-password
        for ($i = 0; $i < self::MAX_ATTEMPTS; $i++) {
            $this->postJson('/api/auth/reset-password', [
                'phone' => $this->phone,
                'otp' => '000000',
                'password' => 'NewPassword1!',
                'password_confirmation' => 'NewPassword1!',
            ]);
        }

        // 6th attempt should be locked
        $response = $this->postJson('/api/auth/reset-password', [
            'phone' => $this->phone,
            'otp' => '000000',
            'password' => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ]);

        $response->assertStatus(429);
    }
}
