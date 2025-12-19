<?php

namespace Tests\Unit\Models;

use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MedicalRecordTest extends TestCase
{
    use RefreshDatabase;

    public function test_medical_record_belongs_to_appointment(): void
    {
        $appointment = Appointment::factory()->create();
        $medicalRecord = MedicalRecord::factory()->forAppointment($appointment)->create();

        $this->assertInstanceOf(Appointment::class, $medicalRecord->appointment);
        $this->assertEquals($appointment->id, $medicalRecord->appointment->id);
    }

    public function test_medical_record_belongs_to_patient(): void
    {
        $patient = User::factory()->patient()->create();
        $medicalRecord = MedicalRecord::factory()->create([
            'patient_id' => $patient->id,
        ]);

        $this->assertInstanceOf(User::class, $medicalRecord->patient);
        $this->assertEquals($patient->id, $medicalRecord->patient->id);
    }

    public function test_medical_record_has_many_prescriptions(): void
    {
        $medicalRecord = MedicalRecord::factory()->create();
        Prescription::factory()->count(3)->create([
            'medical_record_id' => $medicalRecord->id,
        ]);

        $this->assertCount(3, $medicalRecord->prescriptions);
    }

    public function test_has_follow_up_attribute(): void
    {
        $withFollowUp = MedicalRecord::factory()->create([
            'follow_up_date' => now()->addDays(7),
        ]);

        $withoutFollowUp = MedicalRecord::factory()->create([
            'follow_up_date' => null,
        ]);

        $this->assertTrue($withFollowUp->has_follow_up);
        $this->assertFalse($withoutFollowUp->has_follow_up);
    }

    public function test_bmi_calculation(): void
    {
        $medicalRecord = MedicalRecord::factory()->create([
            'vital_signs' => [
                'weight' => 70,
                'height' => 175,
            ],
        ]);

        // BMI = 70 / (1.75 * 1.75) = 22.9
        $this->assertEquals(22.9, $medicalRecord->bmi);
    }

    public function test_bmi_returns_null_when_no_weight_or_height(): void
    {
        $medicalRecord = MedicalRecord::factory()->create([
            'vital_signs' => [
                'weight' => 70,
            ],
        ]);

        $this->assertNull($medicalRecord->bmi);
    }

    public function test_vital_signs_accessors(): void
    {
        $medicalRecord = MedicalRecord::factory()->create([
            'vital_signs' => [
                'blood_pressure' => '120/80',
                'heart_rate' => 75,
                'temperature' => 37.2,
                'weight' => 70,
                'height' => 175,
            ],
        ]);

        $this->assertEquals('120/80', $medicalRecord->blood_pressure);
        $this->assertEquals(75, $medicalRecord->heart_rate);
        $this->assertEquals(37.2, $medicalRecord->temperature);
        $this->assertEquals(70, $medicalRecord->weight);
        $this->assertEquals(175, $medicalRecord->height);
    }

    public function test_scope_for_patient(): void
    {
        $patient = User::factory()->patient()->create();

        MedicalRecord::factory()->count(3)->create([
            'patient_id' => $patient->id,
        ]);

        MedicalRecord::factory()->count(2)->create();

        $this->assertCount(3, MedicalRecord::forPatient($patient->id)->get());
    }

    public function test_scope_follow_up_due(): void
    {
        // Due within 7 days
        MedicalRecord::factory()->create([
            'follow_up_date' => now()->addDays(3),
        ]);

        // Due in more than 7 days
        MedicalRecord::factory()->create([
            'follow_up_date' => now()->addDays(14),
        ]);

        // No follow up
        MedicalRecord::factory()->create([
            'follow_up_date' => null,
        ]);

        $this->assertCount(1, MedicalRecord::followUpDue()->get());
    }
}
