<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Exceptions\BusinessLogicException;
use App\Models\Appointment;
use App\Models\Payment;
use App\Models\User;
use App\Traits\LogsActivity;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ReportService
{
    use LogsActivity;

    protected int $maxReportDays = 365;

    /**
     * Validate date range for reports.
     */
    protected function validateDateRange(?string $fromDate, ?string $toDate): array
    {
        $from = $fromDate ? Carbon::parse($fromDate) : now()->startOfMonth();
        $to = $toDate ? Carbon::parse($toDate) : now()->endOfMonth();

        // Validate from is before to
        if ($from->gt($to)) {
            throw new BusinessLogicException(
                __('تاريخ البداية يجب أن يكون قبل تاريخ النهاية'),
                'INVALID_DATE_RANGE',
                ['from' => $fromDate, 'to' => $toDate]
            );
        }

        // Validate range is not too large
        if ($from->diffInDays($to) > $this->maxReportDays) {
            throw new BusinessLogicException(
                __('نطاق التقرير لا يمكن أن يتجاوز :days يوم', ['days' => $this->maxReportDays]),
                'DATE_RANGE_TOO_LARGE',
                ['from' => $fromDate, 'to' => $toDate, 'max_days' => $this->maxReportDays]
            );
        }

        return [$from, $to];
    }

    public function getAppointmentsReport(
        ?string $fromDate = null,
        ?string $toDate = null,
        ?string $status = null
    ): array {
        [$from, $to] = $this->validateDateRange($fromDate, $toDate);

        $this->logInfo('Generating appointments report', [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'status' => $status,
        ]);

        $query = Appointment::with(['patient'])
            ->whereBetween('appointment_date', [$from->toDateString(), $to->toDateString()]);

        if ($status) {
            $query->where('status', $status);
        }

        $appointments = $query->orderBy('appointment_date')->orderBy('appointment_time')->get();

        $summary = [
            'total' => $appointments->count(),
            'completed' => $appointments->where('status', AppointmentStatus::COMPLETED)->count(),
            'cancelled' => $appointments->where('status', AppointmentStatus::CANCELLED)->count(),
            'no_show' => $appointments->where('status', AppointmentStatus::NO_SHOW)->count(),
            'pending' => $appointments->where('status', AppointmentStatus::PENDING)->count(),
            'confirmed' => $appointments->where('status', AppointmentStatus::CONFIRMED)->count(),
        ];

        $completion_rate = $summary['total'] > 0
            ? round($summary['completed'] / $summary['total'] * 100, 1)
            : 0;

        $cancellation_rate = $summary['total'] > 0
            ? round($summary['cancelled'] / $summary['total'] * 100, 1)
            : 0;

        return [
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'summary' => $summary,
            'completion_rate' => $completion_rate,
            'cancellation_rate' => $cancellation_rate,
            'appointments' => $appointments->map(fn ($apt) => [
                'id' => $apt->id,
                'patient_name' => $apt->patient->name,
                'patient_phone' => $apt->patient->phone,
                'date' => $apt->appointment_date->format('Y-m-d'),
                'time' => $apt->formatted_time,
                'status' => $apt->status->value,
                'status_label' => $apt->status_label,
            ])->toArray(),
        ];
    }

    public function getRevenueReport(
        ?string $fromDate = null,
        ?string $toDate = null,
        ?string $groupBy = 'day'
    ): array {
        [$from, $to] = $this->validateDateRange($fromDate, $toDate);

        // Validate groupBy parameter
        $validGroupBy = ['day', 'week', 'month'];
        if (!in_array($groupBy, $validGroupBy)) {
            throw new BusinessLogicException(
                __('نوع التجميع غير صالح'),
                'INVALID_GROUP_BY',
                ['group_by' => $groupBy, 'valid_values' => $validGroupBy]
            );
        }

        $this->logInfo('Generating revenue report', [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'group_by' => $groupBy,
        ]);

        $payments = Payment::with(['appointment.patient'])
            ->paid()
            ->whereBetween('paid_at', [$from->startOfDay(), $to->endOfDay()])
            ->orderBy('paid_at')
            ->get();

        $totalRevenue = $payments->sum('total');
        $totalDiscount = $payments->sum('discount');
        $totalPayments = $payments->count();
        $averagePayment = $totalPayments > 0 ? $totalRevenue / $totalPayments : 0;

        // Group by period
        $breakdown = $this->groupPaymentsByPeriod($payments, $groupBy, $from, $to);

        // Group by method
        $byMethod = [
            'cash' => $payments->where('method', 'cash')->sum('total'),
            'card' => $payments->where('method', 'card')->sum('total'),
            'wallet' => $payments->where('method', 'wallet')->sum('total'),
        ];

        return [
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'summary' => [
                'total_revenue' => (float) $totalRevenue,
                'total_discount' => (float) $totalDiscount,
                'total_payments' => $totalPayments,
                'average_payment' => round($averagePayment, 2),
            ],
            'by_method' => $byMethod,
            'breakdown' => $breakdown,
            'payments' => $payments->map(fn ($payment) => [
                'id' => $payment->id,
                'patient_name' => $payment->appointment?->patient?->name,
                'amount' => (float) $payment->amount,
                'discount' => (float) $payment->discount,
                'total' => (float) $payment->total,
                'method' => $payment->method->value,
                'method_label' => $payment->method_label,
                'paid_at' => $payment->paid_at->format('Y-m-d H:i'),
            ])->toArray(),
        ];
    }

    public function getPatientsReport(
        ?string $fromDate = null,
        ?string $toDate = null
    ): array {
        // Use year boundaries for default if no dates provided
        $from = $fromDate ? Carbon::parse($fromDate) : now()->startOfYear();
        $to = $toDate ? Carbon::parse($toDate) : now()->endOfYear();

        // Validate from is before to
        if ($from->gt($to)) {
            throw new BusinessLogicException(
                __('تاريخ البداية يجب أن يكون قبل تاريخ النهاية'),
                'INVALID_DATE_RANGE',
                ['from' => $fromDate, 'to' => $toDate]
            );
        }

        $this->logInfo('Generating patients report', [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]);

        $patients = User::where('role', UserRole::PATIENT)
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->withCount(['appointments', 'appointments as completed_appointments_count' => function ($q) {
                $q->where('status', AppointmentStatus::COMPLETED);
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        $totalPatients = $patients->count();
        $activePatients = $patients->where('completed_appointments_count', '>', 0)->count();

        return [
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'summary' => [
                'total_patients' => $totalPatients,
                'active_patients' => $activePatients,
                'inactive_patients' => $totalPatients - $activePatients,
            ],
            'patients' => $patients->map(fn ($patient) => [
                'id' => $patient->id,
                'name' => $patient->name,
                'phone' => $patient->phone,
                'email' => $patient->email,
                'gender' => $patient->gender?->value,
                'registered_at' => $patient->created_at->format('Y-m-d'),
                'total_appointments' => $patient->appointments_count,
                'completed_appointments' => $patient->completed_appointments_count,
            ])->toArray(),
        ];
    }

    public function exportAppointmentsReportToPdf(
        ?string $fromDate = null,
        ?string $toDate = null,
        ?string $status = null
    ): \Barryvdh\DomPDF\PDF {
        $data = $this->getAppointmentsReport($fromDate, $toDate, $status);

        return Pdf::loadView('reports.appointments', $data)
            ->setPaper('a4', 'portrait');
    }

    public function exportRevenueReportToPdf(
        ?string $fromDate = null,
        ?string $toDate = null
    ): \Barryvdh\DomPDF\PDF {
        $data = $this->getRevenueReport($fromDate, $toDate);

        return Pdf::loadView('reports.revenue', $data)
            ->setPaper('a4', 'portrait');
    }

    public function exportPatientsReportToPdf(
        ?string $fromDate = null,
        ?string $toDate = null
    ): \Barryvdh\DomPDF\PDF {
        $data = $this->getPatientsReport($fromDate, $toDate);

        return Pdf::loadView('reports.patients', $data)
            ->setPaper('a4', 'portrait');
    }

    private function groupPaymentsByPeriod(Collection $payments, string $groupBy, Carbon $from, Carbon $to): array
    {
        $breakdown = [];

        if ($groupBy === 'day') {
            $current = $from->copy();
            while ($current->lte($to)) {
                $dateStr = $current->toDateString();
                $dayPayments = $payments->filter(fn ($p) => $p->paid_at->toDateString() === $dateStr);

                $breakdown[] = [
                    'period' => $dateStr,
                    'label' => $current->translatedFormat('D, d M'),
                    'total' => (float) $dayPayments->sum('total'),
                    'count' => $dayPayments->count(),
                ];

                $current->addDay();
            }
        } elseif ($groupBy === 'week') {
            $current = $from->copy()->startOfWeek();
            while ($current->lte($to)) {
                $weekEnd = $current->copy()->endOfWeek();
                $weekPayments = $payments->filter(fn ($p) =>
                    $p->paid_at->gte($current) && $p->paid_at->lte($weekEnd)
                );

                $breakdown[] = [
                    'period' => $current->toDateString(),
                    'label' => $current->format('d M') . ' - ' . $weekEnd->format('d M'),
                    'total' => (float) $weekPayments->sum('total'),
                    'count' => $weekPayments->count(),
                ];

                $current->addWeek();
            }
        } elseif ($groupBy === 'month') {
            $current = $from->copy()->startOfMonth();
            while ($current->lte($to)) {
                $monthEnd = $current->copy()->endOfMonth();
                $monthPayments = $payments->filter(fn ($p) =>
                    $p->paid_at->month === $current->month && $p->paid_at->year === $current->year
                );

                $breakdown[] = [
                    'period' => $current->format('Y-m'),
                    'label' => $current->translatedFormat('F Y'),
                    'total' => (float) $monthPayments->sum('total'),
                    'count' => $monthPayments->count(),
                ];

                $current->addMonth();
            }
        }

        return $breakdown;
    }
}
