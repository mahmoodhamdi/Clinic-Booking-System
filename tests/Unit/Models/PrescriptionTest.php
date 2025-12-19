<?php

namespace Tests\Unit\Models;

use App\Models\MedicalRecord;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrescriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_prescription_belongs_to_medical_record(): void
    {
        $medicalRecord = MedicalRecord::factory()->create();
        $prescription = Prescription::factory()->create([
            'medical_record_id' => $medicalRecord->id,
        ]);

        $this->assertInstanceOf(MedicalRecord::class, $prescription->medicalRecord);
        $this->assertEquals($medicalRecord->id, $prescription->medicalRecord->id);
    }

    public function test_prescription_has_many_items(): void
    {
        $prescription = Prescription::factory()->create();
        PrescriptionItem::factory()->count(3)->create([
            'prescription_id' => $prescription->id,
        ]);

        $this->assertCount(3, $prescription->items);
    }

    public function test_prescription_number_is_auto_generated(): void
    {
        $prescription = Prescription::factory()->create();

        $this->assertNotNull($prescription->prescription_number);
        $this->assertStringStartsWith('RX-', $prescription->prescription_number);
    }

    public function test_prescription_number_increments(): void
    {
        $first = Prescription::factory()->create();
        $second = Prescription::factory()->create();

        $firstNumber = (int) substr($first->prescription_number, -4);
        $secondNumber = (int) substr($second->prescription_number, -4);

        $this->assertEquals($firstNumber + 1, $secondNumber);
    }

    public function test_is_valid_when_valid_until_is_future(): void
    {
        $prescription = Prescription::factory()->create([
            'valid_until' => now()->addDays(7),
        ]);

        $this->assertTrue($prescription->is_valid);
        $this->assertFalse($prescription->is_expired);
    }

    public function test_is_valid_when_valid_until_is_today(): void
    {
        $prescription = Prescription::factory()->create([
            'valid_until' => now(),
        ]);

        $this->assertTrue($prescription->is_valid);
    }

    public function test_is_expired_when_valid_until_is_past(): void
    {
        $prescription = Prescription::factory()->create([
            'valid_until' => now()->subDay(),
        ]);

        $this->assertFalse($prescription->is_valid);
        $this->assertTrue($prescription->is_expired);
    }

    public function test_is_valid_when_no_expiry(): void
    {
        $prescription = Prescription::factory()->create([
            'valid_until' => null,
        ]);

        $this->assertTrue($prescription->is_valid);
        $this->assertFalse($prescription->is_expired);
    }

    public function test_mark_as_dispensed(): void
    {
        $prescription = Prescription::factory()->create([
            'is_dispensed' => false,
            'dispensed_at' => null,
        ]);

        $prescription->markAsDispensed();

        $this->assertTrue($prescription->is_dispensed);
        $this->assertNotNull($prescription->dispensed_at);
    }

    public function test_scope_dispensed(): void
    {
        Prescription::factory()->count(2)->dispensed()->create();
        Prescription::factory()->count(3)->create(['is_dispensed' => false]);

        $this->assertCount(2, Prescription::dispensed()->get());
    }

    public function test_scope_not_dispensed(): void
    {
        Prescription::factory()->count(2)->dispensed()->create();
        Prescription::factory()->count(3)->create(['is_dispensed' => false]);

        $this->assertCount(3, Prescription::notDispensed()->get());
    }

    public function test_scope_valid(): void
    {
        Prescription::factory()->count(2)->valid()->create();
        Prescription::factory()->count(1)->expired()->create();
        Prescription::factory()->count(1)->noExpiry()->create();

        // Valid should include no expiry and future expiry
        $this->assertCount(3, Prescription::valid()->get());
    }

    public function test_scope_expired(): void
    {
        Prescription::factory()->count(2)->valid()->create();
        Prescription::factory()->count(1)->expired()->create();
        Prescription::factory()->count(1)->noExpiry()->create();

        $this->assertCount(1, Prescription::expired()->get());
    }

    public function test_scope_for_patient(): void
    {
        $patient = User::factory()->patient()->create();
        $medicalRecord = MedicalRecord::factory()->create([
            'patient_id' => $patient->id,
        ]);

        Prescription::factory()->count(2)->create([
            'medical_record_id' => $medicalRecord->id,
        ]);

        Prescription::factory()->count(3)->create();

        $this->assertCount(2, Prescription::forPatient($patient->id)->get());
    }

    public function test_patient_accessor(): void
    {
        $patient = User::factory()->patient()->create();
        $medicalRecord = MedicalRecord::factory()->create([
            'patient_id' => $patient->id,
        ]);
        $prescription = Prescription::factory()->create([
            'medical_record_id' => $medicalRecord->id,
        ]);

        $this->assertEquals($patient->id, $prescription->patient->id);
    }
}
