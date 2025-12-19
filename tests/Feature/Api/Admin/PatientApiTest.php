<?php

namespace Tests\Feature\Api\Admin;

use App\Enums\BloodType;
use App\Models\Appointment;
use App\Models\ClinicSetting;
use App\Models\PatientProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PatientApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        ClinicSetting::factory()->create();
    }

    /** @test */
    public function admin_can_list_patients(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        User::factory()->patient()->count(5)->create();

        $response = $this->getJson('/api/admin/patients');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonCount(5, 'data');
    }

    /** @test */
    public function admin_can_search_patients(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        User::factory()->patient()->create(['name' => 'محمد أحمد']);
        User::factory()->patient()->create(['name' => 'محمد علي']);
        User::factory()->patient()->count(3)->create();

        $response = $this->getJson('/api/admin/patients?search=محمد');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function admin_can_filter_patients_by_status(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        User::factory()->patient()->create(['is_active' => true]);
        User::factory()->patient()->create(['is_active' => true]);
        User::factory()->patient()->create(['is_active' => false]);

        $response = $this->getJson('/api/admin/patients?status=active');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function admin_can_filter_patients_by_blood_type(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $patient1 = User::factory()->patient()->create();
        $patient2 = User::factory()->patient()->create();
        $patient3 = User::factory()->patient()->create();

        PatientProfile::factory()->forUser($patient1)->withBloodType(BloodType::A_POSITIVE)->create();
        PatientProfile::factory()->forUser($patient2)->withBloodType(BloodType::A_POSITIVE)->create();
        PatientProfile::factory()->forUser($patient3)->withBloodType(BloodType::B_POSITIVE)->create();

        $response = $this->getJson('/api/admin/patients?blood_type=A%2B');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function admin_can_filter_patients_with_profile(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $patient1 = User::factory()->patient()->create();
        $patient2 = User::factory()->patient()->create();
        User::factory()->patient()->create(); // No profile

        PatientProfile::factory()->forUser($patient1)->create();
        PatientProfile::factory()->forUser($patient2)->create();

        $response = $this->getJson('/api/admin/patients?has_profile=1');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function admin_can_view_single_patient(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $patient = User::factory()->patient()->create();
        PatientProfile::factory()->forUser($patient)->create();

        $response = $this->getJson("/api/admin/patients/{$patient->id}");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $patient->id,
                    'name' => $patient->name,
                    'has_profile' => true,
                ],
            ]);
    }

    /** @test */
    public function admin_can_get_patient_appointments(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $patient = User::factory()->patient()->create();
        Appointment::factory()->forPatient($patient)->count(5)->create();

        $response = $this->getJson("/api/admin/patients/{$patient->id}/appointments");

        $response->assertOk()
            ->assertJsonCount(5, 'data');
    }

    /** @test */
    public function admin_can_get_patient_statistics(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $patient = User::factory()->patient()->create();
        Appointment::factory()->forPatient($patient)->completed()->count(3)->create();
        Appointment::factory()->forPatient($patient)->cancelled()->count(2)->create();

        $response = $this->getJson("/api/admin/patients/{$patient->id}/statistics");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_appointments' => 5,
                    'completed_appointments' => 3,
                    'cancelled_appointments' => 2,
                ],
            ]);
    }

    /** @test */
    public function admin_can_update_patient_profile(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $patient = User::factory()->patient()->create();
        PatientProfile::factory()->forUser($patient)->create();

        $response = $this->putJson("/api/admin/patients/{$patient->id}/profile", [
            'blood_type' => 'AB+',
            'allergies' => ['البنسلين'],
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('patient_profiles', [
            'user_id' => $patient->id,
            'blood_type' => 'AB+',
        ]);
    }

    /** @test */
    public function admin_can_create_profile_for_patient_without_one(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $patient = User::factory()->patient()->create();

        $response = $this->putJson("/api/admin/patients/{$patient->id}/profile", [
            'blood_type' => 'O-',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('patient_profiles', [
            'user_id' => $patient->id,
            'blood_type' => 'O-',
        ]);
    }

    /** @test */
    public function admin_can_toggle_patient_status(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $patient = User::factory()->patient()->create(['is_active' => true]);

        $response = $this->putJson("/api/admin/patients/{$patient->id}/status");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'is_active' => false,
                ],
            ]);

        // Toggle back
        $response = $this->putJson("/api/admin/patients/{$patient->id}/status");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'is_active' => true,
                ],
            ]);
    }

    /** @test */
    public function admin_can_add_notes_to_patient(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $patient = User::factory()->patient()->create();
        PatientProfile::factory()->forUser($patient)->create(['medical_notes' => null]);

        $response = $this->postJson("/api/admin/patients/{$patient->id}/notes", [
            'medical_notes' => 'ملاحظات طبية جديدة',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('patient_profiles', [
            'user_id' => $patient->id,
        ]);

        $this->assertStringContains('ملاحظات طبية جديدة', $patient->profile->fresh()->medical_notes);
    }

    /** @test */
    public function admin_can_get_patients_summary(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        User::factory()->patient()->count(5)->create(['is_active' => true]);
        User::factory()->patient()->count(2)->create(['is_active' => false]);

        $response = $this->getJson('/api/admin/patients/summary');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_patients' => 7,
                    'active_patients' => 5,
                    'inactive_patients' => 2,
                ],
            ]);
    }

    /** @test */
    public function cannot_view_non_patient_user(): void
    {
        $admin = User::factory()->admin()->create();
        $secretary = User::factory()->secretary()->create();
        Sanctum::actingAs($admin);

        $response = $this->getJson("/api/admin/patients/{$secretary->id}");

        $response->assertNotFound();
    }

    /** @test */
    public function non_admin_cannot_access_patient_management(): void
    {
        $patient = User::factory()->patient()->create();
        Sanctum::actingAs($patient);

        $response = $this->getJson('/api/admin/patients');

        $response->assertForbidden();
    }

    /** @test */
    public function unauthenticated_user_cannot_access_patient_management(): void
    {
        $response = $this->getJson('/api/admin/patients');

        $response->assertUnauthorized();
    }

    protected function assertStringContains(string $needle, ?string $haystack): void
    {
        $this->assertNotNull($haystack);
        $this->assertStringContainsString($needle, $haystack);
    }
}
