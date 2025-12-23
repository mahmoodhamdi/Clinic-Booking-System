<?php

namespace Tests\Feature;

use App\Models\MedicalRecord;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrescriptionTest extends TestCase
{
    use RefreshDatabase;

    // ==================== Admin Tests ====================

    public function test_admin_can_list_prescriptions(): void
    {
        $admin = User::factory()->admin()->create();
        Prescription::factory()->count(5)->create();

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/prescriptions');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'prescription_number',
                        'is_dispensed',
                        'is_valid',
                        'items',
                    ],
                ],
            ]);
    }

    public function test_admin_can_filter_prescriptions_by_patient(): void
    {
        $admin = User::factory()->admin()->create();
        $patient = User::factory()->patient()->create();
        $medicalRecord = MedicalRecord::factory()->create([
            'patient_id' => $patient->id,
        ]);

        Prescription::factory()->count(3)->create([
            'medical_record_id' => $medicalRecord->id,
        ]);
        Prescription::factory()->count(2)->create();

        $response = $this->actingAs($admin)
            ->getJson("/api/admin/prescriptions?patient_id={$patient->id}");

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_admin_can_filter_prescriptions_by_dispensed_status(): void
    {
        $admin = User::factory()->admin()->create();

        Prescription::factory()->count(2)->dispensed()->create();
        Prescription::factory()->count(3)->create(['is_dispensed' => false]);

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/prescriptions?dispensed=true');

        $response->assertOk()
            ->assertJsonCount(2, 'data');

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/prescriptions?dispensed=false');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_admin_can_create_prescription_with_items(): void
    {
        $admin = User::factory()->admin()->create();
        $medicalRecord = MedicalRecord::factory()->create();

        $data = [
            'medical_record_id' => $medicalRecord->id,
            'notes' => 'ملاحظات الوصفة',
            'valid_until' => now()->addMonth()->format('Y-m-d'),
            'items' => [
                [
                    'medication_name' => 'أموكسيسيلين',
                    'dosage' => '500 مجم',
                    'frequency' => 'ثلاث مرات يومياً',
                    'duration' => '7 أيام',
                    'instructions' => 'بعد الأكل',
                    'quantity' => 21,
                ],
                [
                    'medication_name' => 'باراسيتامول',
                    'dosage' => '500 مجم',
                    'frequency' => 'عند اللزوم',
                    'duration' => '5 أيام',
                ],
            ],
        ];

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/prescriptions', $data);

        $response->assertCreated()
            ->assertJsonPath('data.notes', 'ملاحظات الوصفة')
            ->assertJsonCount(2, 'data.items');

        $this->assertDatabaseHas('prescriptions', [
            'medical_record_id' => $medicalRecord->id,
        ]);

        $this->assertDatabaseHas('prescription_items', [
            'medication_name' => 'أموكسيسيلين',
        ]);
    }

    public function test_admin_can_view_prescription(): void
    {
        $admin = User::factory()->admin()->create();
        $prescription = Prescription::factory()->create();
        PrescriptionItem::factory()->count(3)->create([
            'prescription_id' => $prescription->id,
        ]);

        $response = $this->actingAs($admin)
            ->getJson("/api/admin/prescriptions/{$prescription->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $prescription->id)
            ->assertJsonCount(3, 'data.items');
    }

    public function test_admin_can_update_prescription(): void
    {
        $admin = User::factory()->admin()->create();
        $prescription = Prescription::factory()->create();
        $item = PrescriptionItem::factory()->create([
            'prescription_id' => $prescription->id,
        ]);

        $data = [
            'notes' => 'ملاحظات محدثة',
            'items' => [
                [
                    'id' => $item->id,
                    'medication_name' => 'دواء محدث',
                    'dosage' => '250 مجم',
                    'frequency' => 'مرتين يومياً',
                    'duration' => '10 أيام',
                ],
            ],
        ];

        $response = $this->actingAs($admin)
            ->putJson("/api/admin/prescriptions/{$prescription->id}", $data);

        $response->assertOk()
            ->assertJsonPath('data.notes', 'ملاحظات محدثة');

        $this->assertDatabaseHas('prescription_items', [
            'id' => $item->id,
            'medication_name' => 'دواء محدث',
        ]);
    }

    public function test_admin_can_add_new_items_when_updating_prescription(): void
    {
        $admin = User::factory()->admin()->create();
        $prescription = Prescription::factory()->create();
        $existingItem = PrescriptionItem::factory()->create([
            'prescription_id' => $prescription->id,
        ]);

        $data = [
            'items' => [
                [
                    'id' => $existingItem->id,
                    'medication_name' => $existingItem->medication_name,
                    'dosage' => $existingItem->dosage,
                    'frequency' => $existingItem->frequency,
                    'duration' => $existingItem->duration,
                ],
                [
                    'medication_name' => 'دواء جديد',
                    'dosage' => '100 مجم',
                    'frequency' => 'مرة يومياً',
                    'duration' => '14 يوم',
                ],
            ],
        ];

        $response = $this->actingAs($admin)
            ->putJson("/api/admin/prescriptions/{$prescription->id}", $data);

        $response->assertOk()
            ->assertJsonCount(2, 'data.items');
    }

    public function test_admin_can_delete_prescription(): void
    {
        $admin = User::factory()->admin()->create();
        $prescription = Prescription::factory()->create();

        $response = $this->actingAs($admin)
            ->deleteJson("/api/admin/prescriptions/{$prescription->id}");

        $response->assertOk();
        $this->assertSoftDeleted('prescriptions', [
            'id' => $prescription->id,
        ]);
    }

    public function test_admin_can_mark_prescription_as_dispensed(): void
    {
        $admin = User::factory()->admin()->create();
        $prescription = Prescription::factory()->create([
            'is_dispensed' => false,
        ]);

        $response = $this->actingAs($admin)
            ->postJson("/api/admin/prescriptions/{$prescription->id}/dispense");

        $response->assertOk()
            ->assertJsonPath('data.is_dispensed', true);

        $this->assertDatabaseHas('prescriptions', [
            'id' => $prescription->id,
            'is_dispensed' => true,
        ]);
    }

    public function test_admin_can_get_prescriptions_by_patient(): void
    {
        $admin = User::factory()->admin()->create();
        $patient = User::factory()->patient()->create();
        $medicalRecord = MedicalRecord::factory()->create([
            'patient_id' => $patient->id,
        ]);

        Prescription::factory()->count(3)->create([
            'medical_record_id' => $medicalRecord->id,
        ]);

        $response = $this->actingAs($admin)
            ->getJson("/api/admin/patients/{$patient->id}/prescriptions");

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    // ==================== Patient Tests ====================

    public function test_patient_can_list_own_prescriptions(): void
    {
        $patient = User::factory()->patient()->create();
        $medicalRecord = MedicalRecord::factory()->create([
            'patient_id' => $patient->id,
        ]);

        Prescription::factory()->count(3)->create([
            'medical_record_id' => $medicalRecord->id,
        ]);

        Prescription::factory()->count(2)->create();

        $response = $this->actingAs($patient)
            ->getJson('/api/prescriptions');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_patient_can_filter_prescriptions_by_status(): void
    {
        $patient = User::factory()->patient()->create();
        $medicalRecord = MedicalRecord::factory()->create([
            'patient_id' => $patient->id,
        ]);

        Prescription::factory()->count(2)->valid()->create([
            'medical_record_id' => $medicalRecord->id,
        ]);
        Prescription::factory()->expired()->create([
            'medical_record_id' => $medicalRecord->id,
        ]);

        $response = $this->actingAs($patient)
            ->getJson('/api/prescriptions?status=valid');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_patient_can_view_own_prescription(): void
    {
        $patient = User::factory()->patient()->create();
        $medicalRecord = MedicalRecord::factory()->create([
            'patient_id' => $patient->id,
        ]);
        $prescription = Prescription::factory()->create([
            'medical_record_id' => $medicalRecord->id,
        ]);

        $response = $this->actingAs($patient)
            ->getJson("/api/prescriptions/{$prescription->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $prescription->id);
    }

    public function test_patient_cannot_view_other_patient_prescription(): void
    {
        $patient = User::factory()->patient()->create();
        $prescription = Prescription::factory()->create();

        $response = $this->actingAs($patient)
            ->getJson("/api/prescriptions/{$prescription->id}");

        $response->assertForbidden();
    }

    // ==================== Validation Tests ====================

    public function test_medical_record_id_is_required(): void
    {
        $admin = User::factory()->admin()->create();

        $data = [
            'items' => [
                [
                    'medication_name' => 'دواء',
                    'dosage' => '500 مجم',
                    'frequency' => 'مرة يومياً',
                    'duration' => '7 أيام',
                ],
            ],
        ];

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/prescriptions', $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['medical_record_id']);
    }

    public function test_items_are_required(): void
    {
        $admin = User::factory()->admin()->create();
        $medicalRecord = MedicalRecord::factory()->create();

        $data = [
            'medical_record_id' => $medicalRecord->id,
        ];

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/prescriptions', $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['items']);
    }

    public function test_item_medication_name_is_required(): void
    {
        $admin = User::factory()->admin()->create();
        $medicalRecord = MedicalRecord::factory()->create();

        $data = [
            'medical_record_id' => $medicalRecord->id,
            'items' => [
                [
                    'dosage' => '500 مجم',
                    'frequency' => 'مرة يومياً',
                    'duration' => '7 أيام',
                ],
            ],
        ];

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/prescriptions', $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0.medication_name']);
    }
}
