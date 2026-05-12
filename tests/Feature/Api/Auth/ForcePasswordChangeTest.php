<?php

namespace Tests\Feature\Api\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ForcePasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_with_must_change_password_can_still_read_their_profile(): void
    {
        $user = User::factory()->create([
            'must_change_password' => true,
            'role' => UserRole::ADMIN,
        ]);
        Sanctum::actingAs($user);

        $this->getJson('/api/auth/me')->assertOk();
    }

    /** @test */
    public function user_with_must_change_password_can_change_their_password(): void
    {
        $user = User::factory()->create([
            'password' => 'OldCl1n1cT3st!2026#',
            'must_change_password' => true,
            'role' => UserRole::ADMIN,
        ]);
        Sanctum::actingAs($user);

        $this->postJson('/api/auth/change-password', [
            'current_password' => 'OldCl1n1cT3st!2026#',
            'password' => 'NewCl1n1cT3st!2026#',
            'password_confirmation' => 'NewCl1n1cT3st!2026#',
        ])->assertOk();

        $this->assertFalse((bool) $user->fresh()->must_change_password);
    }

    /** @test */
    public function user_with_must_change_password_can_log_out(): void
    {
        $user = User::factory()->create([
            'must_change_password' => true,
            'role' => UserRole::ADMIN,
        ]);
        Sanctum::actingAs($user);

        $this->postJson('/api/auth/logout')->assertOk();
    }

    /** @test */
    public function user_with_must_change_password_is_blocked_from_admin_endpoints(): void
    {
        $user = User::factory()->create([
            'must_change_password' => true,
            'role' => UserRole::ADMIN,
        ]);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/admin/dashboard/stats');

        $response->assertStatus(403)
            ->assertJsonPath('error_code', 'PASSWORD_CHANGE_REQUIRED');
    }

    /** @test */
    public function user_with_must_change_password_is_blocked_from_patient_endpoints(): void
    {
        $user = User::factory()->create([
            'must_change_password' => true,
            'role' => UserRole::PATIENT,
        ]);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/appointments');

        $response->assertStatus(403)
            ->assertJsonPath('error_code', 'PASSWORD_CHANGE_REQUIRED');
    }

    /** @test */
    public function user_with_must_change_password_is_blocked_from_profile_update(): void
    {
        $user = User::factory()->create([
            'must_change_password' => true,
            'role' => UserRole::ADMIN,
        ]);
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/auth/profile', ['name' => 'New']);

        $response->assertStatus(403)
            ->assertJsonPath('error_code', 'PASSWORD_CHANGE_REQUIRED');
    }

    /** @test */
    public function flag_is_cleared_after_password_change(): void
    {
        $user = User::factory()->create([
            'password' => 'OldCl1n1cT3st!2026#',
            'must_change_password' => true,
            'role' => UserRole::ADMIN,
        ]);
        Sanctum::actingAs($user);

        $this->postJson('/api/auth/change-password', [
            'current_password' => 'OldCl1n1cT3st!2026#',
            'password' => 'NewCl1n1cT3st!2026#',
            'password_confirmation' => 'NewCl1n1cT3st!2026#',
        ])->assertOk();

        // After clearing the flag, the user should now reach normal endpoints.
        $this->getJson('/api/admin/dashboard/stats')->assertOk();
    }

    /** @test */
    public function flag_is_cleared_after_otp_password_reset(): void
    {
        $user = User::factory()->create([
            'phone' => '01012345678',
            'must_change_password' => true,
        ]);

        $otp = '123456';
        \DB::table('password_reset_tokens')->insert([
            'phone' => '01012345678',
            'token' => Hash::make($otp),
            'attempts' => 0,
            'created_at' => now(),
        ]);

        $this->postJson('/api/auth/reset-password', [
            'phone' => '01012345678',
            'otp' => $otp,
            'password' => 'NewCl1n1cT3st!2026#',
            'password_confirmation' => 'NewCl1n1cT3st!2026#',
        ])->assertOk();

        $this->assertFalse((bool) $user->fresh()->must_change_password);
    }

    /** @test */
    public function user_without_flag_is_unaffected(): void
    {
        $user = User::factory()->create([
            'must_change_password' => false,
            'role' => UserRole::ADMIN,
        ]);
        Sanctum::actingAs($user);

        $this->getJson('/api/admin/dashboard/stats')->assertOk();
    }

    /** @test */
    public function admin_seeded_account_has_must_change_password_set(): void
    {
        $this->seed(AdminSeeder::class);

        $admin = User::where('phone', '01000000000')->first();

        $this->assertNotNull($admin);
        $this->assertTrue((bool) $admin->must_change_password);
    }
}
