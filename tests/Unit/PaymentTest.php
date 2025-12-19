<?php

namespace Tests\Unit;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Appointment;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_payment(): void
    {
        $appointment = Appointment::factory()->create();

        $payment = Payment::factory()->create([
            'appointment_id' => $appointment->id,
            'amount' => 100.00,
            'discount' => 10.00,
            'total' => 90.00,
        ]);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'appointment_id' => $appointment->id,
            'amount' => 100.00,
            'discount' => 10.00,
            'total' => 90.00,
        ]);
    }

    public function test_payment_belongs_to_appointment(): void
    {
        $appointment = Appointment::factory()->create();
        $payment = Payment::factory()->create(['appointment_id' => $appointment->id]);

        $this->assertInstanceOf(Appointment::class, $payment->appointment);
        $this->assertEquals($appointment->id, $payment->appointment->id);
    }

    public function test_payment_status_enum(): void
    {
        $payment = Payment::factory()->pending()->create();
        $this->assertTrue($payment->status->isPending());

        $payment = Payment::factory()->paid()->create();
        $this->assertTrue($payment->status->isPaid());

        $payment = Payment::factory()->refunded()->create();
        $this->assertTrue($payment->status->isRefunded());
    }

    public function test_payment_method_enum(): void
    {
        $payment = Payment::factory()->cash()->create();
        $this->assertEquals(PaymentMethod::CASH, $payment->method);

        $payment = Payment::factory()->card()->create();
        $this->assertEquals(PaymentMethod::CARD, $payment->method);

        $payment = Payment::factory()->wallet()->create();
        $this->assertEquals(PaymentMethod::WALLET, $payment->method);
    }

    public function test_formatted_amount_accessor(): void
    {
        $payment = Payment::factory()->create(['amount' => 150.00]);

        $this->assertStringContainsString('150', $payment->formatted_amount);
    }

    public function test_formatted_total_accessor(): void
    {
        $payment = Payment::factory()->create(['total' => 135.00]);

        $this->assertStringContainsString('135', $payment->formatted_total);
    }

    public function test_has_discount_accessor(): void
    {
        $paymentWithDiscount = Payment::factory()->create(['discount' => 10.00]);
        $paymentWithoutDiscount = Payment::factory()->create(['discount' => 0.00]);

        $this->assertTrue($paymentWithDiscount->has_discount);
        $this->assertFalse($paymentWithoutDiscount->has_discount);
    }

    public function test_discount_percentage_accessor(): void
    {
        $payment = Payment::factory()->create([
            'amount' => 100.00,
            'discount' => 20.00,
            'total' => 80.00,
        ]);

        $this->assertEquals(20.0, $payment->discount_percentage);
    }

    public function test_mark_as_paid_method(): void
    {
        $payment = Payment::factory()->pending()->create();

        $this->assertTrue($payment->status->isPending());

        $payment->markAsPaid();

        $this->assertTrue($payment->status->isPaid());
        $this->assertNotNull($payment->paid_at);
    }

    public function test_refund_method(): void
    {
        $payment = Payment::factory()->paid()->create();

        $this->assertTrue($payment->status->isPaid());

        $payment->refund('Customer request');

        $this->assertTrue($payment->status->isRefunded());
        $this->assertNotNull($payment->refunded_at);
        $this->assertEquals('Customer request', $payment->notes);
    }

    public function test_calculate_total_method(): void
    {
        $total = Payment::calculateTotal(200.00, 50.00);

        $this->assertEquals(150.00, $total);
    }

    public function test_paid_scope(): void
    {
        Payment::factory()->count(3)->paid()->create();
        Payment::factory()->count(2)->pending()->create();

        $paidPayments = Payment::paid()->get();

        $this->assertCount(3, $paidPayments);
    }

    public function test_pending_scope(): void
    {
        Payment::factory()->count(3)->paid()->create();
        Payment::factory()->count(2)->pending()->create();

        $pendingPayments = Payment::pending()->get();

        $this->assertCount(2, $pendingPayments);
    }

    public function test_refunded_scope(): void
    {
        Payment::factory()->count(3)->paid()->create();
        Payment::factory()->count(2)->refunded()->create();

        $refundedPayments = Payment::refunded()->get();

        $this->assertCount(2, $refundedPayments);
    }

    public function test_for_patient_scope(): void
    {
        $patient = User::factory()->create();
        $otherPatient = User::factory()->create();

        $patientAppointment = Appointment::factory()->create(['user_id' => $patient->id]);
        $otherAppointment = Appointment::factory()->create(['user_id' => $otherPatient->id]);

        Payment::factory()->create(['appointment_id' => $patientAppointment->id]);
        Payment::factory()->create(['appointment_id' => $otherAppointment->id]);

        $patientPayments = Payment::forPatient($patient->id)->get();

        $this->assertCount(1, $patientPayments);
    }

    public function test_for_date_range_scope(): void
    {
        Payment::factory()->create(['paid_at' => now()->subDays(5)]);
        Payment::factory()->create(['paid_at' => now()->subDays(2)]);
        Payment::factory()->create(['paid_at' => now()]);

        $payments = Payment::forDateRange(
            now()->subDays(3)->toDateString(),
            now()->toDateString()
        )->get();

        $this->assertCount(2, $payments);
    }

    public function test_by_method_scope(): void
    {
        Payment::factory()->count(3)->cash()->create();
        Payment::factory()->count(2)->card()->create();

        $cashPayments = Payment::byMethod(PaymentMethod::CASH)->get();
        $cardPayments = Payment::byMethod(PaymentMethod::CARD)->get();

        $this->assertCount(3, $cashPayments);
        $this->assertCount(2, $cardPayments);
    }

    public function test_payment_method_labels(): void
    {
        $this->assertEquals('Cash', PaymentMethod::CASH->label());
        $this->assertEquals('Card', PaymentMethod::CARD->label());
        $this->assertEquals('Wallet', PaymentMethod::WALLET->label());
    }

    public function test_payment_method_arabic_labels(): void
    {
        $this->assertEquals('نقداً', PaymentMethod::CASH->labelAr());
        $this->assertEquals('بطاقة', PaymentMethod::CARD->labelAr());
        $this->assertEquals('محفظة', PaymentMethod::WALLET->labelAr());
    }

    public function test_payment_status_colors(): void
    {
        $this->assertEquals('warning', PaymentStatus::PENDING->color());
        $this->assertEquals('success', PaymentStatus::PAID->color());
        $this->assertEquals('danger', PaymentStatus::REFUNDED->color());
    }
}
