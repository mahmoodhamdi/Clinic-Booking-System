<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Appointment;
use App\Models\Payment;
use Illuminate\Support\Carbon;

class PaymentService
{
    public function createPayment(
        Appointment $appointment,
        float $amount,
        PaymentMethod $method = PaymentMethod::CASH,
        float $discount = 0,
        ?string $notes = null
    ): Payment {
        $total = Payment::calculateTotal($amount, $discount);

        return Payment::create([
            'appointment_id' => $appointment->id,
            'amount' => $amount,
            'discount' => $discount,
            'total' => $total,
            'method' => $method,
            'status' => PaymentStatus::PENDING,
            'notes' => $notes,
        ]);
    }

    public function updatePayment(
        Payment $payment,
        array $data
    ): Payment {
        $updateData = [];

        if (isset($data['amount'])) {
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

        return $payment;
    }

    public function markAsPaid(Payment $payment, ?string $transactionId = null): Payment
    {
        return $payment->markAsPaid($transactionId);
    }

    public function refund(Payment $payment, ?string $reason = null): Payment
    {
        return $payment->refund($reason);
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
