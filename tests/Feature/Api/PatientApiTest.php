<?php

namespace Tests\Feature\Api;

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
    public function patient_can_get_dashboard(): void
    {
        $patient = User::factory()->patient()->create();
        Sanctum::actingAs($patient);

        Appointment::factory()
            ->forPatient($patient)
            ->pending()
            ->forDate(now()->addDay()->toDateString())
            ->count(2)
            ->create();

        $response = $this->getJson('/api/patient/dashboard');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => [
                    'user',
                    'profile_complete',
                    'upcoming_appointments',
                    'statistics',
                    'next_appointment',
                ],
            ]);
    }

    /** @test */
    public function patient_can_get_profile(): void
    {
        $patient = User::factory()->patient()->create();
        PatientProfile::factory()->forUser($patient)->create();
        Sanctum::actingAs($patient);

        $response = $this->getJson('/api/patient/profile');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'blood_type',
                    'emergency_contact',
                    'allergies',
                    'chronic_diseases',
                    'current_medications',
                ],
            ]);
    }

    /** @test */
    public function patient_gets_null_when_no_profile(): void
    {
        $patient = User::factory()->patient()->create();
        Sanctum::actingAs($patient);

        $response = $this->getJson('/api/patient/profile');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => null,
            ]);
    }

    /** @test */
    public function patient_can_create_profile(): void
    {
        $patient = User::factory()->patient()->create();
        Sanctum::actingAs($patient);

        $response = $this->postJson('/api/patient/profile', [
            'blood_type' => 'A+',
            'emergency_contact_name' => 'محمد أحمد',
            'emergency_contact_phone' => '+201019793768',
            'allergies' => ['البنسلين', 'الأسبرين'],
            'chronic_diseases' => ['السكري'],
            'insurance_provider' => 'شركة التأمين',
            'insurance_number' => 'INS-123456',
        ]);

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'data' => [
                    'blood_type' => 'A+',
                    'allergies' => ['البنسلين', 'الأسبرين'],
                ],
            ]);

        $this->assertDatabaseHas('patient_profiles', [
            'user_id' => $patient->id,
            'blood_type' => 'A+',
        ]);
    }

    /** @test */
    public function patient_cannot_create_duplicate_profile(): void
    {
        $patient = User::factory()->patient()->create();
        PatientProfile::factory()->forUser($patient)->create();
        Sanctum::actingAs($patient);

        $response = $this->postJson('/api/patient/profile', [
            'blood_type' => 'B+',
        ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    /** @test */
    public function patient_can_update_profile(): void
    {
        $patient = User::factory()->patient()->create();
        PatientProfile::factory()->forUser($patient)->create(['blood_type' => BloodType::A_POSITIVE]);
        Sanctum::actingAs($patient);

        $response = $this->putJson('/api/patient/profile', [
            'blood_type' => 'B+',
            'allergies' => ['السلفا'],
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'blood_type' => 'B+',
                    'allergies' => ['السلفا'],
                ],
            ]);
    }

    /** @test */
    public function patient_can_update_or_create_profile(): void
    {
        $patient = User::factory()->patient()->create();
        Sanctum::actingAs($patient);

        // No profile exists, should create
        $response = $this->putJson('/api/patient/profile', [
            'blood_type' => 'O+',
        ]);

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'data' => [
                    'blood_type' => 'O+',
                ],
            ]);
    }

    /** @test */
    public function patient_can_get_appointment_history(): void
    {
        $patient = User::factory()->patient()->create();
        Sanctum::actingAs($patient);

        Appointment::factory()
            ->forPatient($patient)
            ->count(5)
            ->create();

        $response = $this->getJson('/api/patient/history');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonCount(5, 'data');
    }

    /** @test */
    public function patient_can_filter_history_by_status(): void
    {
        $patient = User::factory()->patient()->create();
        Sanctum::actingAs($patient);

        Appointment::factory()->forPatient($patient)->completed()->count(3)->create();
        Appointment::factory()->forPatient($patient)->cancelled()->count(2)->create();

        $response = $this->getJson('/api/patient/history?status=completed');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function patient_can_get_statistics(): void
    {
        $patient = User::factory()->patient()->create();
        Sanctum::actingAs($patient);

        Appointment::factory()->forPatient($patient)->completed()->count(5)->create();
        Appointment::factory()->forPatient($patient)->cancelled()->count(2)->create();

        $response = $this->getJson('/api/patient/statistics');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_appointments' => 7,
                    'completed_appointments' => 5,
                    'cancelled_appointments' => 2,
                ],
            ]);
    }

    /** @test */
    public function blood_type_validation_works(): void
    {
        $patient = User::factory()->patient()->create();
        Sanctum::actingAs($patient);

        $response = $this->postJson('/api/patient/profile', [
            'blood_type' => 'invalid',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['blood_type']);
    }

    /** @test */
    public function emergency_phone_validation_works(): void
    {
        $patient = User::factory()->patient()->create();
        Sanctum::actingAs($patient);

        $response = $this->postJson('/api/patient/profile', [
            'emergency_contact_phone' => 'not-a-phone',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['emergency_contact_phone']);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_patient_routes(): void
    {
        $response = $this->getJson('/api/patient/dashboard');
        $response->assertUnauthorized();

        $response = $this->getJson('/api/patient/profile');
        $response->assertUnauthorized();
    }
}
