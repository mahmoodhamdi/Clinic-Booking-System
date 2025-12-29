<?php

namespace Tests\Unit\Policies;

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Payment;
use App\Models\User;
use App\Policies\PaymentPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentPolicyTest extends TestCase
{
    use RefreshDatabase;

    private PaymentPolicy $policy;
    private User $admin;
    private User $secretary;
    private User $patient;
    private User $otherPatient;
    private Payment $payment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new PaymentPolicy();

        $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->secretary = User::factory()->create(['role' => UserRole::SECRETARY]);
        $this->patient = User::factory()->create(['role' => UserRole::PATIENT]);
        $this->otherPatient = User::factory()->create(['role' => UserRole::PATIENT]);

        $appointment = Appointment::factory()->create([
            'user_id' => $this->patient->id,
        ]);

        $this->payment = Payment::factory()->create([
            'appointment_id' => $appointment->id,
        ]);
    }

    public function test_admin_can_view_any_payments(): void
    {
        $this->assertTrue($this->policy->viewAny($this->admin));
    }

    public function test_secretary_can_view_any_payments(): void
    {
        $this->assertTrue($this->policy->viewAny($this->secretary));
    }

    public function test_patient_cannot_view_any_payments(): void
    {
        $this->assertFalse($this->policy->viewAny($this->patient));
    }

    public function test_admin_can_view_any_payment(): void
    {
        $this->assertTrue($this->policy->view($this->admin, $this->payment));
    }

    public function test_secretary_can_view_any_payment(): void
    {
        $this->assertTrue($this->policy->view($this->secretary, $this->payment));
    }

    public function test_patient_can_view_own_payment(): void
    {
        $this->assertTrue($this->policy->view($this->patient, $this->payment));
    }

    public function test_patient_cannot_view_other_patient_payment(): void
    {
        $this->assertFalse($this->policy->view($this->otherPatient, $this->payment));
    }

    public function test_admin_can_create_payment(): void
    {
        $this->assertTrue($this->policy->create($this->admin));
    }

    public function test_secretary_can_create_payment(): void
    {
        $this->assertTrue($this->policy->create($this->secretary));
    }

    public function test_patient_cannot_create_payment(): void
    {
        $this->assertFalse($this->policy->create($this->patient));
    }

    public function test_admin_can_update_payment(): void
    {
        $this->assertTrue($this->policy->update($this->admin, $this->payment));
    }

    public function test_secretary_can_update_payment(): void
    {
        $this->assertTrue($this->policy->update($this->secretary, $this->payment));
    }

    public function test_patient_cannot_update_payment(): void
    {
        $this->assertFalse($this->policy->update($this->patient, $this->payment));
    }

    public function test_admin_can_refund_payment(): void
    {
        $this->assertTrue($this->policy->refund($this->admin, $this->payment));
    }

    public function test_secretary_cannot_refund_payment(): void
    {
        $this->assertFalse($this->policy->refund($this->secretary, $this->payment));
    }

    public function test_patient_cannot_refund_payment(): void
    {
        $this->assertFalse($this->policy->refund($this->patient, $this->payment));
    }

    public function test_admin_can_mark_payment_as_paid(): void
    {
        $this->assertTrue($this->policy->markAsPaid($this->admin, $this->payment));
    }

    public function test_secretary_can_mark_payment_as_paid(): void
    {
        $this->assertTrue($this->policy->markAsPaid($this->secretary, $this->payment));
    }

    public function test_patient_cannot_mark_payment_as_paid(): void
    {
        $this->assertFalse($this->policy->markAsPaid($this->patient, $this->payment));
    }
}
