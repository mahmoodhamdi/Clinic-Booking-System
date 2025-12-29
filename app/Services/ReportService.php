<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentMethod;
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
use Illuminate\Support\Facades\DB;

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

        $fromStr = $from->toDateString();
        $toStr = $to->toDateString();

        // Get summary statistics in a single query
        $summaryQuery = Appointment::query()
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as cancelled,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as no_show,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as confirmed
            ", [
                AppointmentStatus::COMPLETED->value,
                AppointmentStatus::CANCELLED->value,
                AppointmentStatus::NO_SHOW->value,
                AppointmentStatus::PENDING->value,
                AppointmentStatus::CONFIRMED->value,
            ])
            ->whereBetween('appointment_date', [$fromStr, $toStr]);

        if ($status) {
            $summaryQuery->where('status', $status);
        }

        $stats = $summaryQuery->first();

        $summary = [
            'total' => (int) $stats->total,
            'completed' => (int) $stats->completed,
            'cancelled' => (int) $stats->cancelled,
            'no_show' => (int) $stats->no_show,
            'pending' => (int) $stats->pending,
            'confirmed' => (int) $stats->confirmed,
        ];

        $completion_rate = $summary['total'] > 0
            ? round($summary['completed'] / $summary['total'] * 100, 1)
            : 0;

        $cancellation_rate = $summary['total'] > 0
            ? round($summary['cancelled'] / $summary['total'] * 100, 1)
            : 0;

        // Get appointments list (only select needed columns)
        $appointmentsQuery = Appointment::query()
            ->select(['id', 'user_id', 'appointment_date', 'appointment_time', 'status'])
            ->with(['patient:id,name,phone'])
            ->whereBetween('appointment_date', [$fromStr, $toStr]);

        if ($status) {
            $appointmentsQuery->where('status', $status);
        }

        $appointments = $appointmentsQuery
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->get();

        return [
            'period' => [
                'from' => $fromStr,
                'to' => $toStr,
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

        $fromDate = $from->copy()->startOfDay();
        $toDate = $to->copy()->endOfDay();

        // Get summary and by_method in a single query
        $summaryStats = Payment::query()
            ->selectRaw("
                COALESCE(SUM(total), 0) as total_revenue,
                COALESCE(SUM(discount), 0) as total_discount,
                COUNT(*) as total_payments,
                COALESCE(SUM(CASE WHEN method = ? THEN total ELSE 0 END), 0) as cash_total,
                COALESCE(SUM(CASE WHEN method = ? THEN total ELSE 0 END), 0) as card_total,
                COALESCE(SUM(CASE WHEN method = ? THEN total ELSE 0 END), 0) as wallet_total
            ", [
                PaymentMethod::CASH->value,
                PaymentMethod::CARD->value,
                PaymentMethod::WALLET->value,
            ])
            ->where('status', PaymentStatus::PAID)
            ->whereBetween('paid_at', [$fromDate, $toDate])
            ->first();

        $totalRevenue = (float) $summaryStats->total_revenue;
        $totalPayments = (int) $summaryStats->total_payments;
        $averagePayment = $totalPayments > 0 ? $totalRevenue / $totalPayments : 0;

        // Get breakdown by period using database GROUP BY
        $breakdown = $this->groupPaymentsByPeriodOptimized($groupBy, $fromDate, $toDate, $from, $to);

        // Get payments list (only select needed columns)
        $payments = Payment::query()
            ->select(['id', 'appointment_id', 'amount', 'discount', 'total', 'method', 'paid_at'])
            ->with(['appointment:id,user_id', 'appointment.patient:id,name'])
            ->where('status', PaymentStatus::PAID)
            ->whereBetween('paid_at', [$fromDate, $toDate])
            ->orderBy('paid_at')
            ->get();

        return [
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'summary' => [
                'total_revenue' => $totalRevenue,
                'total_discount' => (float) $summaryStats->total_discount,
                'total_payments' => $totalPayments,
                'average_payment' => round($averagePayment, 2),
            ],
            'by_method' => [
                'cash' => (float) $summaryStats->cash_total,
                'card' => (float) $summaryStats->card_total,
                'wallet' => (float) $summaryStats->wallet_total,
            ],
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

    /**
     * Group payments by period using database GROUP BY for better performance.
     */
    private function groupPaymentsByPeriodOptimized(
        string $groupBy,
        Carbon $fromDate,
        Carbon $toDate,
        Carbon $from,
        Carbon $to
    ): array {
        // Get aggregated data from database
        if ($groupBy === 'day') {
            $dbResults = Payment::query()
                ->selectRaw('DATE(paid_at) as period_key, SUM(total) as total, COUNT(*) as count')
                ->where('status', PaymentStatus::PAID)
                ->whereBetween('paid_at', [$fromDate, $toDate])
                ->groupBy(DB::raw('DATE(paid_at)'))
                ->get()
                ->keyBy('period_key');

            // Build full date range
            $breakdown = [];
            $current = $from->copy();
            while ($current->lte($to)) {
                $dateStr = $current->toDateString();
                $dbRow = $dbResults->get($dateStr);
                $breakdown[] = [
                    'period' => $dateStr,
                    'label' => $current->translatedFormat('D, d M'),
                    'total' => (float) ($dbRow->total ?? 0),
                    'count' => (int) ($dbRow->count ?? 0),
                ];
                $current->addDay();
            }
            return $breakdown;
        }

        if ($groupBy === 'week') {
            // For weekly, use strftime to get week number
            $dbResults = Payment::query()
                ->selectRaw("strftime('%Y-%W', paid_at) as period_key, SUM(total) as total, COUNT(*) as count")
                ->where('status', PaymentStatus::PAID)
                ->whereBetween('paid_at', [$fromDate, $toDate])
                ->groupBy(DB::raw("strftime('%Y-%W', paid_at)"))
                ->get()
                ->keyBy('period_key');

            $breakdown = [];
            $current = $from->copy()->startOfWeek();
            while ($current->lte($to)) {
                $weekEnd = $current->copy()->endOfWeek();
                $weekKey = $current->format('Y-W');
                $dbRow = $dbResults->get($weekKey);

                $breakdown[] = [
                    'period' => $current->toDateString(),
                    'label' => $current->format('d M') . ' - ' . $weekEnd->format('d M'),
                    'total' => (float) ($dbRow->total ?? 0),
                    'count' => (int) ($dbRow->count ?? 0),
                ];

                $current->addWeek();
            }
            return $breakdown;
        }

        if ($groupBy === 'month') {
            // For monthly, use strftime to get year-month
            $dbResults = Payment::query()
                ->selectRaw("strftime('%Y-%m', paid_at) as period_key, SUM(total) as total, COUNT(*) as count")
                ->where('status', PaymentStatus::PAID)
                ->whereBetween('paid_at', [$fromDate, $toDate])
                ->groupBy(DB::raw("strftime('%Y-%m', paid_at)"))
                ->get()
                ->keyBy('period_key');

            $breakdown = [];
            $current = $from->copy()->startOfMonth();
            while ($current->lte($to)) {
                $monthKey = $current->format('Y-m');
                $dbRow = $dbResults->get($monthKey);

                $breakdown[] = [
                    'period' => $monthKey,
                    'label' => $current->translatedFormat('F Y'),
                    'total' => (float) ($dbRow->total ?? 0),
                    'count' => (int) ($dbRow->count ?? 0),
                ];

                $current->addMonth();
            }
            return $breakdown;
        }

        return [];
    }
}
