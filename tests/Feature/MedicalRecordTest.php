<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MedicalRecordTest extends TestCase
{
    use RefreshDatabase;

    // ==================== Admin Tests ====================

    public function test_admin_can_list_medical_records(): void
    {
        $admin = User::factory()->admin()->create();
        MedicalRecord::factory()->count(5)->create();

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/medical-records');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'diagnosis',
                        'symptoms',
                        'vital_signs',
                        'patient',
                    ],
                ],
            ]);
    }

    public function test_admin_can_filter_medical_records_by_patient(): void
    {
        $admin = User::factory()->admin()->create();
        $patient = User::factory()->patient()->create();

        MedicalRecord::factory()->count(3)->create([
            'patient_id' => $patient->id,
        ]);
        MedicalRecord::factory()->count(2)->create();

        $response = $this->actingAs($admin)
            ->getJson("/api/admin/medical-records?patient_id={$patient->id}");

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_admin_can_create_medical_record(): void
    {
        $admin = User::factory()->admin()->create();
        $appointment = Appointment::factory()->create([
            'status' => AppointmentStatus::COMPLETED,
        ]);

        $data = [
            'appointment_id' => $appointment->id,
            'diagnosis' => 'التهاب الحلق الحاد',
            'symptoms' => 'حمى، سعال، التهاب في الحلق',
            'examination_notes' => 'فحص الحلق يظهر احمرار وتورم',
            'treatment_plan' => 'مضاد حيوي ومسكنات',
            'follow_up_date' => now()->addDays(7)->format('Y-m-d'),
            'vital_signs' => [
                'blood_pressure' => '120/80',
                'heart_rate' => 75,
                'temperature' => 38.2,
            ],
        ];

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/medical-records', $data);

        $response->assertCreated()
            ->assertJsonPath('data.diagnosis', 'التهاب الحلق الحاد')
            ->assertJsonPath('data.patient_id', $appointment->user_id);

        $this->assertDatabaseHas('medical_records', [
            'appointment_id' => $appointment->id,
            'diagnosis' => 'التهاب الحلق الحاد',
        ]);
    }

    public function test_admin_cannot_create_duplicate_medical_record_for_appointment(): void
    {
        $admin = User::factory()->admin()->create();
        $appointment = Appointment::factory()->create();

        MedicalRecord::factory()->create([
            'appointment_id' => $appointment->id,
        ]);

        $data = [
            'appointment_id' => $appointment->id,
            'diagnosis' => 'تشخيص جديد',
        ];

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/medical-records', $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['appointment_id']);
    }

    public function test_admin_can_view_medical_record(): void
    {
        $admin = User::factory()->admin()->create();
        $medicalRecord = MedicalRecord::factory()->create();

        $response = $this->actingAs($admin)
            ->getJson("/api/admin/medical-records/{$medicalRecord->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $medicalRecord->id)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'diagnosis',
                    'symptoms',
                    'vital_signs',
                    'prescriptions',
                    'attachments',
                ],
            ]);
    }

    public function test_admin_can_update_medical_record(): void
    {
        $admin = User::factory()->admin()->create();
        $medicalRecord = MedicalRecord::factory()->create();

        $data = [
            'diagnosis' => 'تشخيص محدث',
            'treatment_plan' => 'خطة علاج جديدة',
        ];

        $response = $this->actingAs($admin)
            ->putJson("/api/admin/medical-records/{$medicalRecord->id}", $data);

        $response->assertOk()
            ->assertJsonPath('data.diagnosis', 'تشخيص محدث');

        $this->assertDatabaseHas('medical_records', [
            'id' => $medicalRecord->id,
            'diagnosis' => 'تشخيص محدث',
        ]);
    }

    public function test_admin_can_delete_medical_record(): void
    {
        $admin = User::factory()->admin()->create();
        $medicalRecord = MedicalRecord::factory()->create();

        $response = $this->actingAs($admin)
            ->deleteJson("/api/admin/medical-records/{$medicalRecord->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('medical_records', [
            'id' => $medicalRecord->id,
        ]);
    }

    public function test_admin_can_get_medical_records_by_patient(): void
    {
        $admin = User::factory()->admin()->create();
        $patient = User::factory()->patient()->create();

        MedicalRecord::factory()->count(3)->create([
            'patient_id' => $patient->id,
        ]);

        $response = $this->actingAs($admin)
            ->getJson("/api/admin/patients/{$patient->id}/medical-records");

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_admin_can_get_follow_ups_due(): void
    {
        $admin = User::factory()->admin()->create();

        // Due within 7 days
        MedicalRecord::factory()->create([
            'follow_up_date' => now()->addDays(3),
        ]);

        // Due in more than 7 days
        MedicalRecord::factory()->create([
            'follow_up_date' => now()->addDays(14),
        ]);

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/medical-records/follow-ups-due');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    // ==================== Patient Tests ====================

    public function test_patient_can_list_own_medical_records(): void
    {
        $patient = User::factory()->patient()->create();

        MedicalRecord::factory()->count(3)->create([
            'patient_id' => $patient->id,
        ]);

        MedicalRecord::factory()->count(2)->create();

        $response = $this->actingAs($patient)
            ->getJson('/api/medical-records');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_patient_can_view_own_medical_record(): void
    {
        $patient = User::factory()->patient()->create();
        $medicalRecord = MedicalRecord::factory()->create([
            'patient_id' => $patient->id,
        ]);

        $response = $this->actingAs($patient)
            ->getJson("/api/medical-records/{$medicalRecord->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $medicalRecord->id);
    }

    public function test_patient_cannot_view_other_patient_medical_record(): void
    {
        $patient = User::factory()->patient()->create();
        $otherPatient = User::factory()->patient()->create();

        $medicalRecord = MedicalRecord::factory()->create([
            'patient_id' => $otherPatient->id,
        ]);

        $response = $this->actingAs($patient)
            ->getJson("/api/medical-records/{$medicalRecord->id}");

        $response->assertForbidden();
    }

    // ==================== Validation Tests ====================

    public function test_diagnosis_is_required(): void
    {
        $admin = User::factory()->admin()->create();
        $appointment = Appointment::factory()->create();

        $data = [
            'appointment_id' => $appointment->id,
        ];

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/medical-records', $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['diagnosis']);
    }

    public function test_follow_up_date_must_be_after_today(): void
    {
        $admin = User::factory()->admin()->create();
        $appointment = Appointment::factory()->create();

        $data = [
            'appointment_id' => $appointment->id,
            'diagnosis' => 'تشخيص',
            'follow_up_date' => now()->subDay()->format('Y-m-d'),
        ];

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/medical-records', $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['follow_up_date']);
    }
}
