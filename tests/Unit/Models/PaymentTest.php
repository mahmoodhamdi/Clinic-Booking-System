<?php

namespace Tests\Unit\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Appointment;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_belongs_to_appointment(): void
    {
        $appointment = Appointment::factory()->create();
        $payment = Payment::factory()->forAppointment($appointment)->create();

        $this->assertInstanceOf(Appointment::class, $payment->appointment);
        $this->assertEquals($appointment->id, $payment->appointment->id);
    }

    public function test_payment_has_patient_accessor(): void
    {
        $patient = User::factory()->patient()->create();
        $appointment = Appointment::factory()->forPatient($patient)->create();
        $payment = Payment::factory()->forAppointment($appointment)->create();

        $this->assertEquals($patient->id, $payment->patient->id);
    }

    public function test_payment_calculates_total(): void
    {
        $amount = 100.00;
        $discount = 20.00;

        $total = Payment::calculateTotal($amount, $discount);

        $this->assertEquals(80.00, $total);
    }

    public function test_payment_total_cannot_be_negative(): void
    {
        $amount = 100.00;
        $discount = 150.00;

        $total = Payment::calculateTotal($amount, $discount);

        $this->assertEquals(0.00, $total);
    }

    public function test_mark_as_paid(): void
    {
        $payment = Payment::factory()->pending()->create();

        $payment->markAsPaid('TXN-123');

        $this->assertEquals(PaymentStatus::PAID, $payment->status);
        $this->assertNotNull($payment->paid_at);
        $this->assertEquals('TXN-123', $payment->transaction_id);
    }

    public function test_refund(): void
    {
        $payment = Payment::factory()->paid()->create();

        $payment->refund('Refund reason');

        $this->assertEquals(PaymentStatus::REFUNDED, $payment->status);
        $this->assertNotNull($payment->refunded_at);
        $this->assertEquals('Refund reason', $payment->notes);
    }

    // ==================== Status Check Tests ====================

    public function test_is_pending(): void
    {
        $pending = Payment::factory()->pending()->create();
        $paid = Payment::factory()->paid()->create();

        $this->assertTrue($pending->isPending());
        $this->assertFalse($paid->isPending());
    }

    public function test_is_paid(): void
    {
        $pending = Payment::factory()->pending()->create();
        $paid = Payment::factory()->paid()->create();

        $this->assertFalse($pending->isPaid());
        $this->assertTrue($paid->isPaid());
    }

    public function test_is_refunded(): void
    {
        $paid = Payment::factory()->paid()->create();
        $refunded = Payment::factory()->refunded()->create();

        $this->assertFalse($paid->isRefunded());
        $this->assertTrue($refunded->isRefunded());
    }

    // ==================== Scope Tests ====================

    public function test_scope_paid(): void
    {
        Payment::factory()->paid()->count(2)->create();
        Payment::factory()->pending()->create();

        $this->assertCount(2, Payment::paid()->get());
    }

    public function test_scope_pending(): void
    {
        Payment::factory()->paid()->create();
        Payment::factory()->pending()->count(3)->create();

        $this->assertCount(3, Payment::pending()->get());
    }

    public function test_scope_refunded(): void
    {
        Payment::factory()->paid()->create();
        Payment::factory()->refunded()->count(2)->create();

        $this->assertCount(2, Payment::refunded()->get());
    }

    public function test_scope_unpaid(): void
    {
        Payment::factory()->paid()->create();
        Payment::factory()->pending()->count(3)->create();

        $this->assertCount(3, Payment::unpaid()->get());
    }

    public function test_scope_for_period(): void
    {
        $from = now()->subDays(7);
        $to = now();

        // Inside period
        Payment::factory()->count(2)->create(['created_at' => now()->subDays(3)]);
        // Outside period
        Payment::factory()->create(['created_at' => now()->subDays(14)]);

        $this->assertCount(2, Payment::forPeriod($from, $to)->get());
    }

    public function test_scope_for_patient(): void
    {
        $patient = User::factory()->patient()->create();
        $appointment = Appointment::factory()->forPatient($patient)->create();
        Payment::factory()->count(2)->forAppointment($appointment)->create();
        Payment::factory()->count(3)->create();

        $this->assertCount(2, Payment::forPatient($patient->id)->get());
    }

    public function test_scope_today(): void
    {
        Payment::factory()->count(2)->create(['created_at' => now()]);
        Payment::factory()->create(['created_at' => now()->subDay()]);

        $this->assertCount(2, Payment::today()->get());
    }

    // ==================== Accessor Tests ====================

    public function test_formatted_amount(): void
    {
        $payment = Payment::factory()->create(['amount' => 150.50]);

        $this->assertEquals('150.50 ج.م', $payment->formatted_amount);
    }

    public function test_has_discount(): void
    {
        $withDiscount = Payment::factory()->create(['discount' => 20.00]);
        $withoutDiscount = Payment::factory()->create(['discount' => 0.00]);

        $this->assertTrue($withDiscount->has_discount);
        $this->assertFalse($withoutDiscount->has_discount);
    }

    public function test_discount_percentage(): void
    {
        $payment = Payment::factory()->create([
            'amount' => 100.00,
            'discount' => 25.00,
        ]);

        $this->assertEquals(25.0, $payment->discount_percentage);
    }

    // ==================== Soft Delete Tests ====================

    public function test_payment_can_be_soft_deleted(): void
    {
        $payment = Payment::factory()->create();
        $payment->delete();

        $this->assertSoftDeleted('payments', ['id' => $payment->id]);
        $this->assertNotNull($payment->fresh()->deleted_at);
    }

    public function test_soft_deleted_payments_are_excluded_by_default(): void
    {
        $active = Payment::factory()->create();
        $deleted = Payment::factory()->create();
        $deleted->delete();

        $payments = Payment::all();

        $this->assertCount(1, $payments);
        $this->assertTrue($payments->contains($active));
        $this->assertFalse($payments->contains($deleted));
    }

    public function test_soft_deleted_payment_can_be_restored(): void
    {
        $payment = Payment::factory()->create();
        $payment->delete();

        $this->assertSoftDeleted('payments', ['id' => $payment->id]);

        $payment->restore();

        $this->assertNull($payment->fresh()->deleted_at);
    }
}
