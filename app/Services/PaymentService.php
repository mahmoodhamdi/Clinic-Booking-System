<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\PaymentException;
use App\Models\Appointment;
use App\Models\Payment;
use App\Traits\LogsActivity;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    use LogsActivity;

    public function createPayment(
        Appointment $appointment,
        float $amount,
        PaymentMethod $method = PaymentMethod::CASH,
        float $discount = 0,
        ?string $notes = null
    ): Payment {
        $this->logInfo('Creating payment', [
            'appointment_id' => $appointment->id,
            'amount' => $amount,
            'method' => $method->value,
        ]);

        // Validate appointment can accept payment
        if ($appointment->status === AppointmentStatus::CANCELLED) {
            throw new PaymentException('appointment_cancelled', $appointment->id, $amount);
        }

        // Check if appointment already has a paid payment
        $existingPayment = $appointment->payment;
        if ($existingPayment && $existingPayment->status === PaymentStatus::PAID) {
            throw new PaymentException('already_paid', $appointment->id, $amount);
        }

        // Validate amount
        if ($amount <= 0) {
            throw new PaymentException('invalid_amount', $appointment->id, $amount);
        }

        return DB::transaction(function () use ($appointment, $amount, $method, $discount, $notes) {
            $total = Payment::calculateTotal($amount, $discount);

            $payment = Payment::create([
                'appointment_id' => $appointment->id,
                'amount' => $amount,
                'discount' => $discount,
                'total' => $total,
                'method' => $method,
                'status' => PaymentStatus::PENDING,
                'notes' => $notes,
            ]);

            $this->logInfo('Payment created successfully', [
                'payment_id' => $payment->id,
                'appointment_id' => $appointment->id,
            ]);

            return $payment;
        });
    }

    public function updatePayment(
        Payment $payment,
        array $data
    ): Payment {
        $this->logInfo('Updating payment', [
            'payment_id' => $payment->id,
            'data' => $data,
        ]);

        // Cannot update paid or refunded payments
        if ($payment->status !== PaymentStatus::PENDING) {
            throw new PaymentException('already_paid', $payment->appointment_id, $payment->amount);
        }

        return DB::transaction(function () use ($payment, $data) {
            $updateData = [];

            if (isset($data['amount'])) {
                if ($data['amount'] <= 0) {
                    throw new PaymentException('invalid_amount', $payment->appointment_id, $data['amount']);
                }
                $updateData['amount'] = $data['amount'];
            }

            if (isset($data['discount'])) {
                $updateData['discount'] = $data['discount'];
            }

            if (isset($data['method'])) {
                $updateData['method'] = $data['method'];
            }

            if (isset($data['notes'])) {
                $updateData['notes'] = $data['notes'];
            }

            // Recalculate total if amount or discount changed
            if (isset($updateData['amount']) || isset($updateData['discount'])) {
                $amount = $updateData['amount'] ?? $payment->amount;
                $discount = $updateData['discount'] ?? $payment->discount;
                $updateData['total'] = Payment::calculateTotal($amount, $discount);
            }

            $payment->update($updateData);

            $this->logInfo('Payment updated successfully', ['payment_id' => $payment->id]);

            return $payment;
        });
    }

    public function markAsPaid(Payment $payment, ?string $transactionId = null): Payment
    {
        $this->logInfo('Marking payment as paid', [
            'payment_id' => $payment->id,
            'transaction_id' => $transactionId,
        ]);

        if ($payment->status === PaymentStatus::PAID) {
            throw new PaymentException('already_paid', $payment->appointment_id, $payment->amount);
        }

        return DB::transaction(function () use ($payment, $transactionId) {
            $result = $payment->markAsPaid($transactionId);
            $this->logInfo('Payment marked as paid', ['payment_id' => $payment->id]);
            return $result;
        });
    }

    public function refund(Payment $payment, ?string $reason = null): Payment
    {
        $this->logInfo('Processing refund', [
            'payment_id' => $payment->id,
            'reason' => $reason,
        ]);

        if ($payment->status !== PaymentStatus::PAID) {
            throw new PaymentException('refund_failed', $payment->appointment_id, $payment->amount);
        }

        return DB::transaction(function () use ($payment, $reason) {
            $result = $payment->refund($reason);
            $this->logInfo('Payment refunded', ['payment_id' => $payment->id]);
            return $result;
        });
    }

    public function getStatistics(?string $from = null, ?string $to = null): array
    {
        $from = $from ?? now()->startOfMonth()->toDateTimeString();
        $to = $to ?? now()->endOfMonth()->toDateTimeString();

        $query = Payment::whereBetween('created_at', [$from, $to]);

        $totalPaid = (clone $query)->paid()->sum('total');
        $totalPending = (clone $query)->pending()->sum('total');
        $totalRefunded = (clone $query)->refunded()->sum('total');
        $totalPayments = (clone $query)->count();
        $paidCount = (clone $query)->paid()->count();
        $pendingCount = (clone $query)->pending()->count();
        $refundedCount = (clone $query)->refunded()->count();

        $byMethod = [];
        foreach (PaymentMethod::cases() as $method) {
            $byMethod[$method->value] = [
                'count' => (clone $query)->paid()->byMethod($method)->count(),
                'total' => (clone $query)->paid()->byMethod($method)->sum('total'),
            ];
        }

        return [
            'total_revenue' => $totalPaid,
            'total_pending' => $totalPending,
            'total_refunded' => $totalRefunded,
            'net_revenue' => $totalPaid - $totalRefunded,
            'total_payments' => $totalPayments,
            'paid_count' => $paidCount,
            'pending_count' => $pendingCount,
            'refunded_count' => $refundedCount,
            'by_method' => $byMethod,
            'period' => [
                'from' => $from,
                'to' => $to,
            ],
        ];
    }

    public function getRevenueReport(string $period = 'month', ?string $year = null): array
    {
        $year = $year ?? now()->year;
        $data = [];

        if ($period === 'month') {
            for ($month = 1; $month <= 12; $month++) {
                $startDate = Carbon::create($year, $month, 1)->startOfMonth();
                $endDate = $startDate->copy()->endOfMonth();

                $revenue = Payment::paid()
                    ->whereBetween('paid_at', [$startDate, $endDate])
                    ->sum('total');

                $refunds = Payment::refunded()
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->sum('total');

                $data[] = [
                    'month' => $month,
                    'month_name' => $startDate->translatedFormat('F'),
                    'revenue' => $revenue,
                    'refunds' => $refunds,
                    'net' => $revenue - $refunds,
                ];
            }
        } elseif ($period === 'week') {
            $startOfMonth = now()->startOfMonth();
            $endOfMonth = now()->endOfMonth();

            $week = 1;
            $currentStart = $startOfMonth->copy();

            while ($currentStart->lte($endOfMonth)) {
                $currentEnd = $currentStart->copy()->endOfWeek();
                if ($currentEnd->gt($endOfMonth)) {
                    $currentEnd = $endOfMonth;
                }

                $revenue = Payment::paid()
                    ->whereBetween('paid_at', [$currentStart, $currentEnd])
                    ->sum('total');

                $data[] = [
                    'week' => $week,
                    'from' => $currentStart->toDateString(),
                    'to' => $currentEnd->toDateString(),
                    'revenue' => $revenue,
                ];

                $currentStart = $currentEnd->copy()->addDay()->startOfDay();
                $week++;
            }
        }

        return [
            'period' => $period,
            'year' => $year,
            'data' => $data,
            'total' => array_sum(array_column($data, $period === 'month' ? 'net' : 'revenue')),
        ];
    }

    public function getTodayStatistics(): array
    {
        $today = now()->toDateString();

        return [
            'total_revenue' => Payment::paid()->whereDate('paid_at', $today)->sum('total'),
            'payments_count' => Payment::paid()->whereDate('paid_at', $today)->count(),
            'pending_count' => Payment::pending()->whereDate('created_at', $today)->count(),
            'pending_amount' => Payment::pending()->whereDate('created_at', $today)->sum('total'),
        ];
    }
}
