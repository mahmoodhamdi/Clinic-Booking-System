<?php

namespace Tests\Feature\Api\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_request_password_reset(): void
    {
        User::factory()->create(['phone' => '01012345678']);

        $response = $this->postJson('/api/auth/forgot-password', [
            'phone' => '01012345678',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('password_reset_tokens', [
            'phone' => '01012345678',
        ]);
    }

    /** @test */
    public function password_reset_fails_for_nonexistent_phone(): void
    {
        $response = $this->postJson('/api/auth/forgot-password', [
            'phone' => '01099999999',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    /** @test */
    public function user_can_verify_otp(): void
    {
        User::factory()->create(['phone' => '01012345678']);

        $token = '123456';
        DB::table('password_reset_tokens')->insert([
            'phone' => '01012345678',
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/auth/verify-otp', [
            'phone' => '01012345678',
            'token' => $token,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function verify_otp_fails_with_wrong_token(): void
    {
        User::factory()->create(['phone' => '01012345678']);

        DB::table('password_reset_tokens')->insert([
            'phone' => '01012345678',
            'token' => Hash::make('123456'),
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/auth/verify-otp', [
            'phone' => '01012345678',
            'token' => '999999',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function verify_otp_fails_with_expired_token(): void
    {
        User::factory()->create(['phone' => '01012345678']);

        $token = '123456';
        DB::table('password_reset_tokens')->insert([
            'phone' => '01012345678',
            'token' => Hash::make($token),
            'created_at' => now()->subMinutes(61), // Expired
        ]);

        $response = $this->postJson('/api/auth/verify-otp', [
            'phone' => '01012345678',
            'token' => $token,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function user_can_reset_password(): void
    {
        User::factory()->create(['phone' => '01012345678']);

        $token = '123456';
        DB::table('password_reset_tokens')->insert([
            'phone' => '01012345678',
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/auth/reset-password', [
            'phone' => '01012345678',
            'token' => $token,
            'password' => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        // Verify password was changed
        $user = User::where('phone', '01012345678')->first();
        $this->assertTrue(Hash::check('NewPassword1!', $user->password));

        // Verify token was deleted
        $this->assertDatabaseMissing('password_reset_tokens', [
            'phone' => '01012345678',
        ]);
    }

    /** @test */
    public function reset_password_fails_with_invalid_token(): void
    {
        User::factory()->create(['phone' => '01012345678']);

        DB::table('password_reset_tokens')->insert([
            'phone' => '01012345678',
            'token' => Hash::make('123456'),
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/auth/reset-password', [
            'phone' => '01012345678',
            'token' => '999999',
            'password' => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function reset_password_requires_password_confirmation(): void
    {
        User::factory()->create(['phone' => '01012345678']);

        $token = '123456';
        DB::table('password_reset_tokens')->insert([
            'phone' => '01012345678',
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/auth/reset-password', [
            'phone' => '01012345678',
            'token' => $token,
            'password' => 'NewPassword1!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }
}
