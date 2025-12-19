<?php

namespace Tests\Feature\Api\Admin;

use App\Models\ClinicSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ClinicSettingsApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admin_can_get_settings(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        ClinicSetting::factory()->create(['clinic_name' => 'عيادة الاختبار']);

        $response = $this->getJson('/api/admin/settings');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'clinic_name' => 'عيادة الاختبار',
                ],
            ]);
    }

    /** @test */
    public function admin_can_update_settings(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        ClinicSetting::factory()->create();

        $response = $this->putJson('/api/admin/settings', [
            'clinic_name' => 'عيادة جديدة',
            'doctor_name' => 'د. محمد',
            'slot_duration' => 45,
            'max_patients_per_slot' => 2,
            'advance_booking_days' => 14,
            'cancellation_hours' => 12,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'clinic_name' => 'عيادة جديدة',
                    'slot_duration' => 45,
                ],
            ]);

        $this->assertDatabaseHas('clinic_settings', [
            'clinic_name' => 'عيادة جديدة',
            'slot_duration' => 45,
        ]);
    }

    /** @test */
    public function admin_can_upload_logo(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        ClinicSetting::factory()->create();

        $file = UploadedFile::fake()->image('logo.png', 200, 200);

        $response = $this->postJson('/api/admin/settings/logo', [
            'logo' => $file,
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $settings = ClinicSetting::first();
        $this->assertNotNull($settings->logo);
        Storage::disk('public')->assertExists($settings->logo);
    }

    /** @test */
    public function admin_can_delete_logo(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $logoPath = 'logos/test-logo.png';
        Storage::disk('public')->put($logoPath, 'fake content');

        ClinicSetting::factory()->create(['logo' => $logoPath]);

        $response = $this->deleteJson('/api/admin/settings/logo');

        $response->assertOk()
            ->assertJson(['success' => true]);

        $settings = ClinicSetting::first();
        $this->assertNull($settings->logo);
    }

    /** @test */
    public function non_admin_cannot_access_settings(): void
    {
        $patient = User::factory()->patient()->create();
        Sanctum::actingAs($patient);

        ClinicSetting::factory()->create();

        $response = $this->getJson('/api/admin/settings');

        $response->assertForbidden();
    }

    /** @test */
    public function unauthenticated_user_cannot_access_settings(): void
    {
        ClinicSetting::factory()->create();

        $response = $this->getJson('/api/admin/settings');

        $response->assertUnauthorized();
    }

    /** @test */
    public function update_settings_validates_required_fields(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        ClinicSetting::factory()->create();

        $response = $this->putJson('/api/admin/settings', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'clinic_name',
                'doctor_name',
                'slot_duration',
                'max_patients_per_slot',
                'advance_booking_days',
                'cancellation_hours',
            ]);
    }

    /** @test */
    public function update_settings_validates_slot_duration_range(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        ClinicSetting::factory()->create();

        $response = $this->putJson('/api/admin/settings', [
            'clinic_name' => 'عيادة',
            'doctor_name' => 'دكتور',
            'slot_duration' => 5, // Too short
            'max_patients_per_slot' => 1,
            'advance_booking_days' => 30,
            'cancellation_hours' => 24,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['slot_duration']);
    }

    /** @test */
    public function logo_must_be_image(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        ClinicSetting::factory()->create();

        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->postJson('/api/admin/settings/logo', [
            'logo' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['logo']);
    }
}
