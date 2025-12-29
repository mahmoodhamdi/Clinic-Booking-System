<?php

namespace Tests\Unit\Policies;

use App\Enums\UserRole;
use App\Models\PatientProfile;
use App\Models\User;
use App\Policies\PatientProfilePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientProfilePolicyTest extends TestCase
{
    use RefreshDatabase;

    private PatientProfilePolicy $policy;
    private User $admin;
    private User $secretary;
    private User $patient;
    private User $otherPatient;
    private PatientProfile $patientProfile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new PatientProfilePolicy();

        $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->secretary = User::factory()->create(['role' => UserRole::SECRETARY]);
        $this->patient = User::factory()->create(['role' => UserRole::PATIENT]);
        $this->otherPatient = User::factory()->create(['role' => UserRole::PATIENT]);

        $this->patientProfile = PatientProfile::factory()->create([
            'user_id' => $this->patient->id,
        ]);
    }

    public function test_admin_can_view_any_patient_profiles(): void
    {
        $this->assertTrue($this->policy->viewAny($this->admin));
    }

    public function test_secretary_can_view_any_patient_profiles(): void
    {
        $this->assertTrue($this->policy->viewAny($this->secretary));
    }

    public function test_patient_cannot_view_any_patient_profiles(): void
    {
        $this->assertFalse($this->policy->viewAny($this->patient));
    }

    public function test_admin_can_view_any_patient_profile(): void
    {
        $this->assertTrue($this->policy->view($this->admin, $this->patientProfile));
    }

    public function test_secretary_can_view_any_patient_profile(): void
    {
        $this->assertTrue($this->policy->view($this->secretary, $this->patientProfile));
    }

    public function test_patient_can_view_own_profile(): void
    {
        $this->assertTrue($this->policy->view($this->patient, $this->patientProfile));
    }

    public function test_patient_cannot_view_other_patient_profile(): void
    {
        $this->assertFalse($this->policy->view($this->otherPatient, $this->patientProfile));
    }

    public function test_patient_can_create_profile(): void
    {
        $this->assertTrue($this->policy->create($this->patient));
    }

    public function test_admin_cannot_create_patient_profile(): void
    {
        $this->assertFalse($this->policy->create($this->admin));
    }

    public function test_secretary_cannot_create_patient_profile(): void
    {
        $this->assertFalse($this->policy->create($this->secretary));
    }

    public function test_admin_can_update_any_patient_profile(): void
    {
        $this->assertTrue($this->policy->update($this->admin, $this->patientProfile));
    }

    public function test_secretary_can_update_any_patient_profile(): void
    {
        $this->assertTrue($this->policy->update($this->secretary, $this->patientProfile));
    }

    public function test_patient_can_update_own_profile(): void
    {
        $this->assertTrue($this->policy->update($this->patient, $this->patientProfile));
    }

    public function test_patient_cannot_update_other_patient_profile(): void
    {
        $this->assertFalse($this->policy->update($this->otherPatient, $this->patientProfile));
    }

    public function test_admin_can_delete_patient_profile(): void
    {
        $this->assertTrue($this->policy->delete($this->admin, $this->patientProfile));
    }

    public function test_secretary_cannot_delete_patient_profile(): void
    {
        $this->assertFalse($this->policy->delete($this->secretary, $this->patientProfile));
    }

    public function test_patient_cannot_delete_own_profile(): void
    {
        $this->assertFalse($this->policy->delete($this->patient, $this->patientProfile));
    }
}
