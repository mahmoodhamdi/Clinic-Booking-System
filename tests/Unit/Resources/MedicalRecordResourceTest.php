<?php

namespace Tests\Unit\Resources;

use App\Http\Resources\MedicalRecordResource;
use App\Models\Appointment;
use App\Models\Attachment;
use App\Models\MedicalRecord;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MedicalRecordResourceTest extends TestCase
{
    use RefreshDatabase;

    protected MedicalRecord $medicalRecord;

    protected Appointment $appointment;

    protected User $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->patient = User::factory()->patient()->create();
        $this->appointment = Appointment::factory()->forPatient($this->patient)->create();
        $this->medicalRecord = MedicalRecord::factory()
            ->forAppointment($this->appointment)
            ->forPatient($this->patient)
            ->create();
    }

    /** @test */
    public function resource_transforms_model_correctly(): void
    {
        $resource = new MedicalRecordResource($this->medicalRecord);
        $data = $resource->toArray(request());

        $this->assertEquals($this->medicalRecord->id, $data['id']);
        $this->assertEquals($this->medicalRecord->appointment_id, $data['appointment_id']);
        $this->assertEquals($this->medicalRecord->patient_id, $data['patient_id']);
        $this->assertEquals($this->medicalRecord->diagnosis, $data['diagnosis']);
        $this->assertEquals($this->medicalRecord->symptoms, $data['symptoms']);
        $this->assertEquals($this->medicalRecord->examination_notes, $data['examination_notes']);
        $this->assertEquals($this->medicalRecord->treatment_plan, $data['treatment_plan']);
    }

    /** @test */
    public function resource_includes_vital_signs(): void
    {
        $medicalRecord = MedicalRecord::factory()
            ->forPatient($this->patient)
            ->create([
                'vital_signs' => [
                    'blood_pressure' => '120/80',
                    'heart_rate' => 75,
                    'temperature' => 37.2,
                    'weight' => 70,
                    'height' => 175,
                ],
            ]);

        $resource = new MedicalRecordResource($medicalRecord);
        $data = $resource->toArray(request());

        $this->assertIsArray($data['vital_signs']);
        $this->assertEquals('120/80', $data['vital_signs']['blood_pressure']);
        $this->assertEquals(75, $data['vital_signs']['heart_rate']);
        $this->assertEquals(37.2, $data['vital_signs']['temperature']);
        $this->assertEquals(70, $data['vital_signs']['weight']);
        $this->assertEquals(175, $data['vital_signs']['height']);
    }

    /** @test */
    public function resource_calculates_bmi(): void
    {
        $medicalRecord = MedicalRecord::factory()
            ->forPatient($this->patient)
            ->create([
                'vital_signs' => [
                    'weight' => 70,
                    'height' => 175,
                ],
            ]);

        $resource = new MedicalRecordResource($medicalRecord);
        $data = $resource->toArray(request());

        // BMI = 70 / (1.75 * 1.75) = 22.9
        $this->assertNotNull($data['bmi']);
        $this->assertEquals(22.9, $data['bmi']);
    }

    /** @test */
    public function resource_includes_attachments_when_loaded(): void
    {
        // Create attachments for this medical record
        Attachment::factory()->count(3)->create([
            'attachable_type' => MedicalRecord::class,
            'attachable_id' => $this->medicalRecord->id,
        ]);

        // Load with attachments
        $medicalRecord = MedicalRecord::with('attachments')->find($this->medicalRecord->id);

        $resource = new MedicalRecordResource($medicalRecord);
        $data = $resource->toArray(request());

        // When loaded, should include attachments collection
        $this->assertNotNull($data['attachments']);
        $this->assertCount(3, $data['attachments']);
    }

    /** @test */
    public function resource_includes_patient_when_loaded(): void
    {
        $medicalRecord = MedicalRecord::with('patient')->find($this->medicalRecord->id);

        $resource = new MedicalRecordResource($medicalRecord);
        $data = $resource->toArray(request());

        // When patient is loaded, should include user data
        $this->assertNotNull($data['patient']);
        $this->assertArrayHasKey('id', $data['patient']);
        $this->assertEquals($this->patient->id, $data['patient']['id']);
    }

    /** @test */
    public function resource_includes_appointment_when_loaded(): void
    {
        $medicalRecord = MedicalRecord::with('appointment')->find($this->medicalRecord->id);

        $resource = new MedicalRecordResource($medicalRecord);
        $data = $resource->toArray(request());

        // When appointment is loaded, should include appointment data
        $this->assertNotNull($data['appointment']);
        $this->assertArrayHasKey('id', $data['appointment']);
        $this->assertEquals($this->appointment->id, $data['appointment']['id']);
    }

    /** @test */
    public function resource_includes_prescriptions_when_loaded(): void
    {
        Prescription::factory()->count(2)->create([
            'medical_record_id' => $this->medicalRecord->id,
        ]);

        $medicalRecord = MedicalRecord::with('prescriptions')->find($this->medicalRecord->id);

        $resource = new MedicalRecordResource($medicalRecord);
        $data = $resource->toArray(request());

        // The prescriptions should be present when loaded
        $this->assertNotNull($data['prescriptions']);
    }

    /** @test */
    public function resource_handles_null_optional_fields(): void
    {
        $medicalRecord = MedicalRecord::factory()
            ->forPatient($this->patient)
            ->create([
                'examination_notes' => null,
                'treatment_plan' => null,
                'follow_up_date' => null,
                'follow_up_notes' => null,
            ]);

        $resource = new MedicalRecordResource($medicalRecord);
        $data = $resource->toArray(request());

        $this->assertNull($data['examination_notes']);
        $this->assertNull($data['treatment_plan']);
        $this->assertNull($data['follow_up_date']);
        $this->assertNull($data['follow_up_notes']);
    }

    /** @test */
    public function resource_handles_follow_up_date_formatting(): void
    {
        $followUpDate = now()->addDays(7);
        $medicalRecord = MedicalRecord::factory()
            ->forPatient($this->patient)
            ->create([
                'follow_up_date' => $followUpDate,
            ]);

        $resource = new MedicalRecordResource($medicalRecord);
        $data = $resource->toArray(request());

        $this->assertIsString($data['follow_up_date']);
        $this->assertEquals($followUpDate->format('Y-m-d'), $data['follow_up_date']);
    }

    /** @test */
    public function resource_collection_works_correctly(): void
    {
        // Create multiple medical records
        MedicalRecord::factory()->count(3)->forPatient($this->patient)->create();

        $medicalRecords = MedicalRecord::forPatient($this->patient->id)->get();
        $resource = MedicalRecordResource::collection($medicalRecords);
        $collectionData = $resource->toArray(request());

        // Should return array of resources
        $this->assertIsArray($collectionData);
        $this->assertCount(4, $collectionData); // 1 from setUp + 3 created here
    }

    /** @test */
    public function resource_with_all_vital_signs_populated(): void
    {
        $medicalRecord = MedicalRecord::factory()
            ->forPatient($this->patient)
            ->create([
                'vital_signs' => [
                    'blood_pressure' => '130/85',
                    'heart_rate' => 82,
                    'temperature' => 37.5,
                    'weight' => 75,
                    'height' => 180,
                ],
            ]);

        $resource = new MedicalRecordResource($medicalRecord);
        $data = $resource->toArray(request());

        // Verify all vital signs are present
        $this->assertArrayHasKey('vital_signs', $data);
        $vitalSigns = $data['vital_signs'];
        $this->assertArrayHasKey('blood_pressure', $vitalSigns);
        $this->assertArrayHasKey('heart_rate', $vitalSigns);
        $this->assertArrayHasKey('temperature', $vitalSigns);
        $this->assertArrayHasKey('weight', $vitalSigns);
        $this->assertArrayHasKey('height', $vitalSigns);

        // Verify values
        $this->assertEquals('130/85', $vitalSigns['blood_pressure']);
        $this->assertEquals(82, $vitalSigns['heart_rate']);
        $this->assertEquals(37.5, $vitalSigns['temperature']);
        $this->assertEquals(75, $vitalSigns['weight']);
        $this->assertEquals(180, $vitalSigns['height']);
    }

    /** @test */
    public function resource_includes_timestamps(): void
    {
        $resource = new MedicalRecordResource($this->medicalRecord);
        $data = $resource->toArray(request());

        $this->assertArrayHasKey('created_at', $data);
        $this->assertArrayHasKey('updated_at', $data);

        // Verify they are properly formatted
        $this->assertIsString($data['created_at']);
        $this->assertIsString($data['updated_at']);
    }

    /** @test */
    public function resource_includes_counts_when_not_loaded(): void
    {
        Prescription::factory()->count(2)->create([
            'medical_record_id' => $this->medicalRecord->id,
        ]);

        Attachment::factory()->count(3)->create([
            'attachable_type' => MedicalRecord::class,
            'attachable_id' => $this->medicalRecord->id,
        ]);

        $medicalRecord = MedicalRecord::find($this->medicalRecord->id);
        $resource = new MedicalRecordResource($medicalRecord);
        $data = $resource->toArray(request());

        // Counts should be available even when collections aren't loaded
        $this->assertEquals(2, $data['prescriptions_count']);
        $this->assertEquals(3, $data['attachments_count']);
    }

    /** @test */
    public function resource_has_follow_up_attribute(): void
    {
        $withFollowUp = MedicalRecord::factory()
            ->forPatient($this->patient)
            ->create([
                'follow_up_date' => now()->addDays(7),
            ]);

        $withoutFollowUp = MedicalRecord::factory()
            ->forPatient($this->patient)
            ->create([
                'follow_up_date' => null,
            ]);

        $resource1 = new MedicalRecordResource($withFollowUp);
        $data1 = $resource1->toArray(request());

        $resource2 = new MedicalRecordResource($withoutFollowUp);
        $data2 = $resource2->toArray(request());

        $this->assertTrue($data1['has_follow_up']);
        $this->assertFalse($data2['has_follow_up']);
    }
}
