<?php

namespace Tests\Unit\Policies;

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\Prescription;
use App\Models\User;
use App\Policies\PrescriptionPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrescriptionPolicyTest extends TestCase
{
    use RefreshDatabase;

    private PrescriptionPolicy $policy;
    private User $admin;
    private User $secretary;
    private User $patient;
    private User $otherPatient;
    private Prescription $prescription;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new PrescriptionPolicy();

        $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->secretary = User::factory()->create(['role' => UserRole::SECRETARY]);
        $this->patient = User::factory()->create(['role' => UserRole::PATIENT]);
        $this->otherPatient = User::factory()->create(['role' => UserRole::PATIENT]);

        $appointment = Appointment::factory()->create([
            'user_id' => $this->patient->id,
        ]);

        $medicalRecord = MedicalRecord::factory()->create([
            'appointment_id' => $appointment->id,
            'patient_id' => $this->patient->id,
        ]);

        $this->prescription = Prescription::factory()->create([
            'medical_record_id' => $medicalRecord->id,
        ]);
    }

    public function test_admin_can_view_any_prescriptions(): void
    {
        $this->assertTrue($this->policy->viewAny($this->admin));
    }

    public function test_secretary_can_view_any_prescriptions(): void
    {
        $this->assertTrue($this->policy->viewAny($this->secretary));
    }

    public function test_patient_cannot_view_any_prescriptions(): void
    {
        $this->assertFalse($this->policy->viewAny($this->patient));
    }

    public function test_admin_can_view_any_prescription(): void
    {
        $this->assertTrue($this->policy->view($this->admin, $this->prescription));
    }

    public function test_secretary_can_view_any_prescription(): void
    {
        $this->assertTrue($this->policy->view($this->secretary, $this->prescription));
    }

    public function test_patient_can_view_own_prescription(): void
    {
        $this->assertTrue($this->policy->view($this->patient, $this->prescription));
    }

    public function test_patient_cannot_view_other_patient_prescription(): void
    {
        $this->assertFalse($this->policy->view($this->otherPatient, $this->prescription));
    }

    public function test_admin_can_create_prescription(): void
    {
        $this->assertTrue($this->policy->create($this->admin));
    }

    public function test_secretary_can_create_prescription(): void
    {
        $this->assertTrue($this->policy->create($this->secretary));
    }

    public function test_patient_cannot_create_prescription(): void
    {
        $this->assertFalse($this->policy->create($this->patient));
    }

    public function test_admin_can_update_prescription(): void
    {
        $this->assertTrue($this->policy->update($this->admin, $this->prescription));
    }

    public function test_secretary_can_update_prescription(): void
    {
        $this->assertTrue($this->policy->update($this->secretary, $this->prescription));
    }

    public function test_patient_cannot_update_prescription(): void
    {
        $this->assertFalse($this->policy->update($this->patient, $this->prescription));
    }

    public function test_admin_can_delete_prescription(): void
    {
        $this->assertTrue($this->policy->delete($this->admin, $this->prescription));
    }

    public function test_secretary_cannot_delete_prescription(): void
    {
        $this->assertFalse($this->policy->delete($this->secretary, $this->prescription));
    }

    public function test_patient_cannot_delete_prescription(): void
    {
        $this->assertFalse($this->policy->delete($this->patient, $this->prescription));
    }

    public function test_admin_can_dispense_prescription(): void
    {
        $this->assertTrue($this->policy->dispense($this->admin, $this->prescription));
    }

    public function test_secretary_can_dispense_prescription(): void
    {
        $this->assertTrue($this->policy->dispense($this->secretary, $this->prescription));
    }

    public function test_patient_cannot_dispense_prescription(): void
    {
        $this->assertFalse($this->policy->dispense($this->patient, $this->prescription));
    }

    public function test_admin_can_download_any_prescription_pdf(): void
    {
        $this->assertTrue($this->policy->downloadPdf($this->admin, $this->prescription));
    }

    public function test_secretary_can_download_any_prescription_pdf(): void
    {
        $this->assertTrue($this->policy->downloadPdf($this->secretary, $this->prescription));
    }

    public function test_patient_can_download_own_prescription_pdf(): void
    {
        $this->assertTrue($this->policy->downloadPdf($this->patient, $this->prescription));
    }

    public function test_patient_cannot_download_other_patient_prescription_pdf(): void
    {
        $this->assertFalse($this->policy->downloadPdf($this->otherPatient, $this->prescription));
    }
}
