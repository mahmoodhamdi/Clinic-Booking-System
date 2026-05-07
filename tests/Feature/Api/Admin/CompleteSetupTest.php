<?php

namespace Tests\Feature\Api\Admin;

use App\Enums\UserRole;
use App\Models\ClinicSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CompleteSetupTest extends TestCase
{
    use RefreshDatabase;

    private function actingAdmin(): User
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        Sanctum::actingAs($admin);

        return $admin;
    }

    /** @test */
    public function fresh_install_reports_setup_incomplete(): void
    {
        $this->actingAdmin();
        ClinicSetting::create([
            'clinic_name' => 'العيادة',
            'doctor_name' => 'الدكتور',
            'slot_duration' => 30,
            'max_patients_per_slot' => 1,
            'advance_booking_days' => 30,
            'cancellation_hours' => 24,
        ]);

        $this->getJson('/api/admin/settings')
            ->assertOk()
            ->assertJsonPath('data.is_setup_complete', false);
    }

    /** @test */
    public function complete_setup_rejects_when_clinic_name_is_default_placeholder(): void
    {
        $this->actingAdmin();
        ClinicSetting::create([
            'clinic_name' => 'العيادة',
            'doctor_name' => 'Dr. Real',
            'phone' => '+201012345678',
            'slot_duration' => 30,
            'max_patients_per_slot' => 1,
            'advance_booking_days' => 30,
            'cancellation_hours' => 24,
        ]);

        $this->postJson('/api/admin/settings/complete-setup')
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'SETUP_INCOMPLETE')
            ->assertJsonPath('errors.missing', ['clinic_name']);
    }

    /** @test */
    public function complete_setup_rejects_when_phone_is_blank(): void
    {
        $this->actingAdmin();
        ClinicSetting::create([
            'clinic_name' => 'Cairo Clinic',
            'doctor_name' => 'Dr. Real',
            'phone' => null,
            'slot_duration' => 30,
            'max_patients_per_slot' => 1,
            'advance_booking_days' => 30,
            'cancellation_hours' => 24,
        ]);

        $this->postJson('/api/admin/settings/complete-setup')
            ->assertStatus(422)
            ->assertJsonPath('errors.missing', ['phone']);
    }

    /** @test */
    public function complete_setup_succeeds_with_real_data(): void
    {
        $this->actingAdmin();
        ClinicSetting::create([
            'clinic_name' => 'Cairo Clinic',
            'doctor_name' => 'Dr. Real',
            'phone' => '+201012345678',
            'slot_duration' => 30,
            'max_patients_per_slot' => 1,
            'advance_booking_days' => 30,
            'cancellation_hours' => 24,
        ]);

        $this->postJson('/api/admin/settings/complete-setup')
            ->assertOk()
            ->assertJsonPath('data.is_setup_complete', true)
            ->assertJsonStructure(['data' => ['setup_completed_at']]);

        $this->getJson('/api/admin/settings')
            ->assertOk()
            ->assertJsonPath('data.is_setup_complete', true);
    }

    /** @test */
    public function non_admin_cannot_complete_setup(): void
    {
        $patient = User::factory()->create(['role' => UserRole::PATIENT]);
        Sanctum::actingAs($patient);

        ClinicSetting::create([
            'clinic_name' => 'Cairo Clinic',
            'doctor_name' => 'Dr. Real',
            'phone' => '+201012345678',
            'slot_duration' => 30,
            'max_patients_per_slot' => 1,
            'advance_booking_days' => 30,
            'cancellation_hours' => 24,
        ]);

        $this->postJson('/api/admin/settings/complete-setup')->assertStatus(403);
    }
}
