<?php

namespace Tests\Unit\Services;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\PaymentException;
use App\Models\Appointment;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private PaymentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PaymentService;
    }

    private function appointment(AppointmentStatus $status = AppointmentStatus::CONFIRMED): Appointment
    {
        $patient = User::factory()->create();

        return Appointment::factory()->create([
            'user_id' => $patient->id,
            'status' => $status,
        ]);
    }

    // ==================== createPayment ====================

    /** @test */
    public function create_payment_persists_a_pending_payment_with_the_right_total(): void
    {
        $appointment = $this->appointment();

        $payment = $this->service->createPayment($appointment, 200.0, PaymentMethod::CASH, 50.0, 'first visit');

        $this->assertSame(200.0, (float) $payment->amount);
        $this->assertSame(50.0, (float) $payment->discount);
        $this->assertSame(150.0, (float) $payment->total);
        $this->assertSame(PaymentMethod::CASH, $payment->method);
        $this->assertSame(PaymentStatus::PENDING, $payment->status);
        $this->assertSame('first visit', $payment->notes);
        $this->assertSame($appointment->id, $payment->appointment_id);
    }

    /** @test */
    public function create_payment_defaults_method_to_cash_and_discount_to_zero(): void
    {
        $appointment = $this->appointment();

        $payment = $this->service->createPayment($appointment, 100.0);

        $this->assertSame(PaymentMethod::CASH, $payment->method);
        $this->assertSame(0.0, (float) $payment->discount);
        $this->assertSame(100.0, (float) $payment->total);
    }

    /** @test */
    public function create_payment_clamps_negative_total_to_zero(): void
    {
        $appointment = $this->appointment();

        // Payment::calculateTotal uses max(0, amount - discount), so an
        // over-discount produces a 0 total rather than a negative.
        $payment = $this->service->createPayment($appointment, 100.0, PaymentMethod::CASH, 200.0);

        $this->assertSame(0.0, (float) $payment->total);
    }

    /** @test */
    public function create_payment_rejects_zero_or_negative_amount(): void
    {
        $appointment = $this->appointment();

        foreach ([0.0, -1.0, -100.0] as $bad) {
            try {
                $this->service->createPayment($appointment, $bad);
                $this->fail("amount $bad should have thrown");
            } catch (PaymentException $e) {
                // PaymentException hardcodes errorCode='PAYMENT_ERROR' for
                // every reason; the per-reason discriminator lives in
                // context['reason'].
                $this->assertSame('PAYMENT_ERROR', $e->getErrorCode());
                $this->assertSame('invalid_amount', $e->getContext()['reason']);
                $this->assertSame($appointment->id, $e->getAppointmentId());
                $this->assertSame($bad, $e->getAmount());
            }
        }
    }

    /** @test */
    public function create_payment_rejects_cancelled_appointments(): void
    {
        $appointment = $this->appointment(AppointmentStatus::CANCELLED);

        $this->expectException(PaymentException::class);
        $this->service->createPayment($appointment, 100.0);
    }

    /** @test */
    public function create_payment_rejects_when_appointment_already_has_a_paid_payment(): void
    {
        $appointment = $this->appointment();
        $existing = $this->service->createPayment($appointment, 200.0);
        $this->service->markAsPaid($existing);

        // The service reads $appointment->payment, which is cached on the
        // model instance from earlier access. Re-fetch so the relation
        // reflects the just-paid payment.
        $appointment = $appointment->fresh();

        $this->expectException(PaymentException::class);
        $this->service->createPayment($appointment, 50.0);
    }

    /** @test */
    public function create_payment_allows_a_second_attempt_when_the_first_is_still_pending(): void
    {
        $appointment = $this->appointment();
        $this->service->createPayment($appointment, 200.0);

        // No exception — pending isn't a blocker (use case: edit/recreate
        // before the patient pays).
        $second = $this->service->createPayment($appointment, 250.0);
        $this->assertSame(PaymentStatus::PENDING, $second->status);
    }

    // ==================== updatePayment ====================

    /** @test */
    public function update_payment_recalculates_total_when_amount_changes(): void
    {
        $payment = $this->service->createPayment($this->appointment(), 100.0, PaymentMethod::CASH, 10.0);

        $this->service->updatePayment($payment, ['amount' => 300.0]);

        $this->assertSame(300.0, (float) $payment->fresh()->amount);
        $this->assertSame(290.0, (float) $payment->fresh()->total); // 300 - 10
    }

    /** @test */
    public function update_payment_recalculates_total_when_discount_changes(): void
    {
        $payment = $this->service->createPayment($this->appointment(), 200.0, PaymentMethod::CASH, 0.0);

        $this->service->updatePayment($payment, ['discount' => 50.0]);

        $this->assertSame(150.0, (float) $payment->fresh()->total);
    }

    /** @test */
    public function update_payment_changes_method_and_notes(): void
    {
        $payment = $this->service->createPayment($this->appointment(), 100.0, PaymentMethod::CASH);

        $this->service->updatePayment($payment, [
            'method' => PaymentMethod::CARD,
            'notes' => 'switched to card',
        ]);

        $payment->refresh();
        $this->assertSame(PaymentMethod::CARD, $payment->method);
        $this->assertSame('switched to card', $payment->notes);
    }

    /** @test */
    public function update_payment_rejects_when_payment_is_already_paid(): void
    {
        $payment = $this->service->createPayment($this->appointment(), 100.0);
        $this->service->markAsPaid($payment);

        $this->expectException(PaymentException::class);
        $this->service->updatePayment($payment->fresh(), ['amount' => 200.0]);
    }

    /** @test */
    public function update_payment_rejects_zero_or_negative_amount(): void
    {
        $payment = $this->service->createPayment($this->appointment(), 100.0);

        $this->expectException(PaymentException::class);
        $this->service->updatePayment($payment, ['amount' => 0]);
    }

    // ==================== markAsPaid ====================

    /** @test */
    public function mark_as_paid_sets_status_paid_at_and_transaction_id(): void
    {
        $payment = $this->service->createPayment($this->appointment(), 100.0);

        $result = $this->service->markAsPaid($payment, 'TXN-12345');

        $this->assertSame(PaymentStatus::PAID, $result->status);
        $this->assertNotNull($result->paid_at);
        $this->assertSame('TXN-12345', $result->transaction_id);
    }

    /** @test */
    public function mark_as_paid_rejects_already_paid(): void
    {
        $payment = $this->service->createPayment($this->appointment(), 100.0);
        $this->service->markAsPaid($payment);

        $this->expectException(PaymentException::class);
        $this->service->markAsPaid($payment->fresh());
    }

    // ==================== refund ====================

    /** @test */
    public function refund_only_works_on_paid_payments(): void
    {
        $payment = $this->service->createPayment($this->appointment(), 100.0);
        $this->service->markAsPaid($payment);

        $refunded = $this->service->refund($payment->fresh(), 'patient cancelled');

        $this->assertSame(PaymentStatus::REFUNDED, $refunded->status);
        $this->assertNotNull($refunded->refunded_at);
        $this->assertSame('patient cancelled', $refunded->notes);
    }

    /** @test */
    public function refund_rejects_pending_payments(): void
    {
        $payment = $this->service->createPayment($this->appointment(), 100.0);

        $this->expectException(PaymentException::class);
        $this->service->refund($payment);
    }

    /** @test */
    public function refund_rejects_already_refunded_payments(): void
    {
        $payment = $this->service->createPayment($this->appointment(), 100.0);
        $this->service->markAsPaid($payment);
        $this->service->refund($payment->fresh());

        $this->expectException(PaymentException::class);
        $this->service->refund($payment->fresh());
    }

    // ==================== getStatistics ====================

    /** @test */
    public function get_statistics_aggregates_paid_pending_and_refunded_correctly(): void
    {
        Carbon::setTestNow('2026-05-15 12:00:00');
        $appointment = $this->appointment();

        $paid1 = $this->service->createPayment($appointment, 200.0);
        $this->service->markAsPaid($paid1);

        $paid2 = $this->service->createPayment($this->appointment(), 300.0);
        $this->service->markAsPaid($paid2);

        $this->service->createPayment($this->appointment(), 100.0); // pending

        $refunded = $this->service->createPayment($this->appointment(), 150.0);
        $this->service->markAsPaid($refunded);
        $this->service->refund($refunded->fresh());

        $stats = $this->service->getStatistics();

        $this->assertSame(500.0, (float) $stats['total_revenue']); // paid1 + paid2 (refunded excluded from total_revenue per current model scope)
        $this->assertSame(100.0, (float) $stats['total_pending']);
        $this->assertSame(150.0, (float) $stats['total_refunded']);
        $this->assertSame(350.0, (float) $stats['net_revenue']);
        $this->assertSame(4, $stats['total_payments']);
        $this->assertSame(2, $stats['paid_count']);
        $this->assertSame(1, $stats['pending_count']);
        $this->assertSame(1, $stats['refunded_count']);
        $this->assertArrayHasKey('cash', $stats['by_method']);
        $this->assertArrayHasKey('from', $stats['period']);
    }

    /** @test */
    public function get_statistics_respects_date_range(): void
    {
        Carbon::setTestNow('2026-05-15 12:00:00');

        $oldPaid = $this->service->createPayment($this->appointment(), 100.0);
        $this->service->markAsPaid($oldPaid);
        Payment::where('id', $oldPaid->id)->update(['paid_at' => '2026-04-01 10:00:00', 'created_at' => '2026-04-01 10:00:00']);

        $newPaid = $this->service->createPayment($this->appointment(), 200.0);
        $this->service->markAsPaid($newPaid);

        $stats = $this->service->getStatistics('2026-05-01 00:00:00', '2026-05-31 23:59:59');

        $this->assertSame(200.0, (float) $stats['total_revenue']);
        $this->assertSame(1, $stats['paid_count']);
    }

    // ==================== getRevenueReport ====================

    /** @test */
    public function get_revenue_report_returns_12_months_for_month_period(): void
    {
        $report = $this->service->getRevenueReport('month', '2026');

        $this->assertSame('month', $report['period']);
        $this->assertSame('2026', $report['year']);
        $this->assertCount(12, $report['data']);
        $this->assertArrayHasKey('month', $report['data'][0]);
        $this->assertArrayHasKey('revenue', $report['data'][0]);
        $this->assertArrayHasKey('refunds', $report['data'][0]);
        $this->assertArrayHasKey('net', $report['data'][0]);
    }

    /** @test */
    public function get_revenue_report_aggregates_paid_revenue_into_the_correct_month(): void
    {
        $payment = $this->service->createPayment($this->appointment(), 500.0);
        $this->service->markAsPaid($payment);
        Payment::where('id', $payment->id)->update(['paid_at' => '2026-03-15 10:00:00']);

        $report = $this->service->getRevenueReport('month', '2026');

        $march = collect($report['data'])->firstWhere('month', 3);
        $this->assertSame(500.0, (float) $march['revenue']);
    }

    /** @test */
    public function get_revenue_report_for_week_period_returns_weeks_in_current_month(): void
    {
        Carbon::setTestNow('2026-05-15 12:00:00');

        $report = $this->service->getRevenueReport('week');

        $this->assertSame('week', $report['period']);
        $this->assertGreaterThanOrEqual(4, count($report['data']));
        $this->assertArrayHasKey('week', $report['data'][0]);
        $this->assertArrayHasKey('from', $report['data'][0]);
        $this->assertArrayHasKey('to', $report['data'][0]);
    }

    // ==================== getTodayStatistics ====================

    /** @test */
    public function get_today_statistics_only_counts_today(): void
    {
        Carbon::setTestNow('2026-05-15 12:00:00');

        $todayPaid = $this->service->createPayment($this->appointment(), 100.0);
        $this->service->markAsPaid($todayPaid);

        $todayPending = $this->service->createPayment($this->appointment(), 50.0);

        // Older payment that should be excluded
        $oldPaid = $this->service->createPayment($this->appointment(), 999.0);
        $this->service->markAsPaid($oldPaid);
        Payment::where('id', $oldPaid->id)->update([
            'paid_at' => '2026-05-10 10:00:00',
            'created_at' => '2026-05-10 10:00:00',
        ]);

        $stats = $this->service->getTodayStatistics();

        $this->assertSame(100.0, (float) $stats['total_revenue']);
        $this->assertSame(1, $stats['payments_count']);
        $this->assertSame(1, $stats['pending_count']);
        $this->assertSame(50.0, (float) $stats['pending_amount']);
    }
}
