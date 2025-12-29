<?php

namespace Tests\Unit\Policies;

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\User;
use App\Policies\AppointmentPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentPolicyTest extends TestCase
{
    use RefreshDatabase;

    private AppointmentPolicy $policy;
    private User $admin;
    private User $secretary;
    private User $patient;
    private User $otherPatient;
    private Appointment $appointment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new AppointmentPolicy();

        $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->secretary = User::factory()->create(['role' => UserRole::SECRETARY]);
        $this->patient = User::factory()->create(['role' => UserRole::PATIENT]);
        $this->otherPatient = User::factory()->create(['role' => UserRole::PATIENT]);

        $this->appointment = Appointment::factory()->create([
            'user_id' => $this->patient->id,
        ]);
    }

    public function test_admin_can_view_any_appointments(): void
    {
        $this->assertTrue($this->policy->viewAny($this->admin));
    }

    public function test_secretary_can_view_any_appointments(): void
    {
        $this->assertTrue($this->policy->viewAny($this->secretary));
    }

    public function test_patient_cannot_view_any_appointments(): void
    {
        $this->assertFalse($this->policy->viewAny($this->patient));
    }

    public function test_admin_can_view_any_appointment(): void
    {
        $this->assertTrue($this->policy->view($this->admin, $this->appointment));
    }

    public function test_secretary_can_view_any_appointment(): void
    {
        $this->assertTrue($this->policy->view($this->secretary, $this->appointment));
    }

    public function test_patient_can_view_own_appointment(): void
    {
        $this->assertTrue($this->policy->view($this->patient, $this->appointment));
    }

    public function test_patient_cannot_view_other_patient_appointment(): void
    {
        $this->assertFalse($this->policy->view($this->otherPatient, $this->appointment));
    }

    public function test_patient_can_create_appointment(): void
    {
        $this->assertTrue($this->policy->create($this->patient));
    }

    public function test_admin_cannot_create_appointment(): void
    {
        $this->assertFalse($this->policy->create($this->admin));
    }

    public function test_secretary_cannot_create_appointment(): void
    {
        $this->assertFalse($this->policy->create($this->secretary));
    }

    public function test_admin_can_cancel_any_appointment(): void
    {
        $this->assertTrue($this->policy->cancel($this->admin, $this->appointment));
    }

    public function test_secretary_can_cancel_any_appointment(): void
    {
        $this->assertTrue($this->policy->cancel($this->secretary, $this->appointment));
    }

    public function test_patient_can_cancel_own_appointment(): void
    {
        $this->assertTrue($this->policy->cancel($this->patient, $this->appointment));
    }

    public function test_patient_cannot_cancel_other_patient_appointment(): void
    {
        $this->assertFalse($this->policy->cancel($this->otherPatient, $this->appointment));
    }

    public function test_admin_can_confirm_appointment(): void
    {
        $this->assertTrue($this->policy->confirm($this->admin, $this->appointment));
    }

    public function test_secretary_can_confirm_appointment(): void
    {
        $this->assertTrue($this->policy->confirm($this->secretary, $this->appointment));
    }

    public function test_patient_cannot_confirm_appointment(): void
    {
        $this->assertFalse($this->policy->confirm($this->patient, $this->appointment));
    }

    public function test_admin_can_complete_appointment(): void
    {
        $this->assertTrue($this->policy->complete($this->admin, $this->appointment));
    }

    public function test_secretary_can_complete_appointment(): void
    {
        $this->assertTrue($this->policy->complete($this->secretary, $this->appointment));
    }

    public function test_patient_cannot_complete_appointment(): void
    {
        $this->assertFalse($this->policy->complete($this->patient, $this->appointment));
    }

    public function test_admin_can_mark_no_show(): void
    {
        $this->assertTrue($this->policy->markNoShow($this->admin, $this->appointment));
    }

    public function test_secretary_can_mark_no_show(): void
    {
        $this->assertTrue($this->policy->markNoShow($this->secretary, $this->appointment));
    }

    public function test_patient_cannot_mark_no_show(): void
    {
        $this->assertFalse($this->policy->markNoShow($this->patient, $this->appointment));
    }

    public function test_admin_can_update_notes(): void
    {
        $this->assertTrue($this->policy->updateNotes($this->admin, $this->appointment));
    }

    public function test_secretary_can_update_notes(): void
    {
        $this->assertTrue($this->policy->updateNotes($this->secretary, $this->appointment));
    }

    public function test_patient_cannot_update_notes(): void
    {
        $this->assertFalse($this->policy->updateNotes($this->patient, $this->appointment));
    }
}
