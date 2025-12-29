<?php

namespace Tests\Unit\Policies;

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\User;
use App\Policies\MedicalRecordPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MedicalRecordPolicyTest extends TestCase
{
    use RefreshDatabase;

    private MedicalRecordPolicy $policy;
    private User $admin;
    private User $secretary;
    private User $patient;
    private User $otherPatient;
    private MedicalRecord $medicalRecord;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new MedicalRecordPolicy();

        $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->secretary = User::factory()->create(['role' => UserRole::SECRETARY]);
        $this->patient = User::factory()->create(['role' => UserRole::PATIENT]);
        $this->otherPatient = User::factory()->create(['role' => UserRole::PATIENT]);

        $appointment = Appointment::factory()->create([
            'user_id' => $this->patient->id,
        ]);

        $this->medicalRecord = MedicalRecord::factory()->create([
            'appointment_id' => $appointment->id,
            'patient_id' => $this->patient->id,
        ]);
    }

    public function test_admin_can_view_any_medical_records(): void
    {
        $this->assertTrue($this->policy->viewAny($this->admin));
    }

    public function test_secretary_can_view_any_medical_records(): void
    {
        $this->assertTrue($this->policy->viewAny($this->secretary));
    }

    public function test_patient_cannot_view_any_medical_records(): void
    {
        $this->assertFalse($this->policy->viewAny($this->patient));
    }

    public function test_admin_can_view_any_medical_record(): void
    {
        $this->assertTrue($this->policy->view($this->admin, $this->medicalRecord));
    }

    public function test_secretary_can_view_any_medical_record(): void
    {
        $this->assertTrue($this->policy->view($this->secretary, $this->medicalRecord));
    }

    public function test_patient_can_view_own_medical_record(): void
    {
        $this->assertTrue($this->policy->view($this->patient, $this->medicalRecord));
    }

    public function test_patient_cannot_view_other_patient_medical_record(): void
    {
        $this->assertFalse($this->policy->view($this->otherPatient, $this->medicalRecord));
    }

    public function test_admin_can_create_medical_record(): void
    {
        $this->assertTrue($this->policy->create($this->admin));
    }

    public function test_secretary_can_create_medical_record(): void
    {
        $this->assertTrue($this->policy->create($this->secretary));
    }

    public function test_patient_cannot_create_medical_record(): void
    {
        $this->assertFalse($this->policy->create($this->patient));
    }

    public function test_admin_can_update_medical_record(): void
    {
        $this->assertTrue($this->policy->update($this->admin, $this->medicalRecord));
    }

    public function test_secretary_can_update_medical_record(): void
    {
        $this->assertTrue($this->policy->update($this->secretary, $this->medicalRecord));
    }

    public function test_patient_cannot_update_medical_record(): void
    {
        $this->assertFalse($this->policy->update($this->patient, $this->medicalRecord));
    }

    public function test_admin_can_delete_medical_record(): void
    {
        $this->assertTrue($this->policy->delete($this->admin, $this->medicalRecord));
    }

    public function test_secretary_cannot_delete_medical_record(): void
    {
        $this->assertFalse($this->policy->delete($this->secretary, $this->medicalRecord));
    }

    public function test_patient_cannot_delete_medical_record(): void
    {
        $this->assertFalse($this->policy->delete($this->patient, $this->medicalRecord));
    }
}
