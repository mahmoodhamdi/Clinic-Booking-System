<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\BusinessLogicException;
use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\Payment;
use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    use LogsActivity;

    protected int $cacheTtl;

    public function __construct()
    {
        $this->cacheTtl = config('clinic.cache.dashboard_ttl', 600);
    }

    public function getOverviewStatistics(): array
    {
        $this->logInfo('Fetching overview statistics');

        return Cache::remember('dashboard_stats', $this->cacheTtl, function () {
            return [
                'total_patients' => $this->getTotalPatients(),
                'total_appointments' => $this->getTotalAppointments(),
                'total_revenue' => $this->getTotalRevenue(),
                'pending_appointments' => $this->getPendingAppointmentsCount(),
                'today_appointments' => $this->getTodayAppointmentsCount(),
                'this_week_appointments' => $this->getThisWeekAppointmentsCount(),
                'this_month_revenue' => $this->getThisMonthRevenue(),
                'this_month_appointments' => $this->getThisMonthAppointmentsCount(),
            ];
        });
    }

    /**
     * Invalidate dashboard cache.
     */
    public function invalidateCache(): void
    {
        Cache::forget('dashboard_stats');
        $this->logInfo('Dashboard cache invalidated');
    }

    public function getTodayStatistics(): array
    {
        $today = now()->toDateString();

        // Single query for all appointment stats
        $appointmentStats = Appointment::query()
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as confirmed,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as cancelled,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as no_show
            ", [
                AppointmentStatus::PENDING->value,
                AppointmentStatus::CONFIRMED->value,
                AppointmentStatus::COMPLETED->value,
                AppointmentStatus::CANCELLED->value,
                AppointmentStatus::NO_SHOW->value,
            ])
            ->whereDate('appointment_date', $today)
            ->first();

        // Single query for all payment stats
        $paymentStats = Payment::query()
            ->selectRaw("
                COALESCE(SUM(total), 0) as total,
                COALESCE(SUM(CASE WHEN status = ? THEN total ELSE 0 END), 0) as paid,
                COALESCE(SUM(CASE WHEN status = ? THEN total ELSE 0 END), 0) as pending
            ", [
                PaymentStatus::PAID->value,
                PaymentStatus::PENDING->value,
            ])
            ->whereDate('created_at', $today)
            ->first();

        return [
            'appointments' => [
                'total' => (int) $appointmentStats->total,
                'pending' => (int) $appointmentStats->pending,
                'confirmed' => (int) $appointmentStats->confirmed,
                'completed' => (int) $appointmentStats->completed,
                'cancelled' => (int) $appointmentStats->cancelled,
                'no_show' => (int) $appointmentStats->no_show,
            ],
            'revenue' => [
                'total' => (float) $paymentStats->total,
                'paid' => (float) $paymentStats->paid,
                'pending' => (float) $paymentStats->pending,
            ],
            'next_appointment' => $this->getNextAppointment(),
        ];
    }

    public function getWeeklyStatistics(): array
    {
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();

        return [
            'appointments' => Appointment::whereBetween('appointment_date', [$startOfWeek, $endOfWeek])->count(),
            'completed' => Appointment::whereBetween('appointment_date', [$startOfWeek, $endOfWeek])
                ->where('status', AppointmentStatus::COMPLETED)->count(),
            'revenue' => Payment::paid()
                ->whereBetween('paid_at', [$startOfWeek, $endOfWeek])
                ->sum('total'),
            'new_patients' => User::where('role', 'patient')
                ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
                ->count(),
        ];
    }

    public function getMonthlyStatistics(?int $month = null, ?int $year = null): array
    {
        $month = $month ?? now()->month;
        $year = $year ?? now()->year;

        // Validate month
        if ($month < 1 || $month > 12) {
            throw new BusinessLogicException(
                __('الشهر غير صالح'),
                'INVALID_MONTH',
                ['month' => $month]
            );
        }

        // Validate year (reasonable range)
        $currentYear = now()->year;
        if ($year < $currentYear - 10 || $year > $currentYear + 1) {
            throw new BusinessLogicException(
                __('السنة غير صالحة'),
                'INVALID_YEAR',
                ['year' => $year]
            );
        }

        $this->logInfo('Fetching monthly statistics', ['month' => $month, 'year' => $year]);

        $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        return [
            'appointments' => Appointment::whereBetween('appointment_date', [$startOfMonth, $endOfMonth])->count(),
            'completed' => Appointment::whereBetween('appointment_date', [$startOfMonth, $endOfMonth])
                ->where('status', AppointmentStatus::COMPLETED)->count(),
            'cancelled' => Appointment::whereBetween('appointment_date', [$startOfMonth, $endOfMonth])
                ->where('status', AppointmentStatus::CANCELLED)->count(),
            'revenue' => Payment::paid()
                ->whereBetween('paid_at', [$startOfMonth, $endOfMonth])
                ->sum('total'),
            'new_patients' => User::where('role', 'patient')
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->count(),
            'average_daily_appointments' => round(
                Appointment::whereBetween('appointment_date', [$startOfMonth, $endOfMonth])->count() / $startOfMonth->daysInMonth,
                1
            ),
        ];
    }

    public function getChartData(string $period = 'week'): array
    {
        // Validate period
        $validPeriods = ['week', 'month'];
        if (!in_array($period, $validPeriods)) {
            throw new BusinessLogicException(
                __('الفترة غير صالحة'),
                'INVALID_PERIOD',
                ['period' => $period, 'valid_values' => $validPeriods]
            );
        }

        $this->logInfo('Fetching chart data', ['period' => $period]);

        return [
            'appointments_trend' => $this->getAppointmentsTrend($period),
            'revenue_trend' => $this->getRevenueTrend($period),
            'status_distribution' => $this->getStatusDistribution(),
            'payment_methods' => $this->getPaymentMethodsDistribution(),
        ];
    }

    public function getAppointmentsTrend(string $period = 'week'): array
    {
        $days = $period === 'week' ? 7 : 30;
        $startDate = now()->subDays($days - 1)->startOfDay();
        $endDate = now()->endOfDay();

        // Single query for all days
        $appointments = Appointment::query()
            ->selectRaw('DATE(appointment_date) as date, COUNT(*) as count')
            ->whereBetween('appointment_date', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(appointment_date)'))
            ->pluck('count', 'date')
            ->toArray();

        // Build data array with all dates
        $data = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i);
            $dateString = $date->toDateString();

            $data[] = [
                'date' => $dateString,
                'day' => $date->translatedFormat('D'),
                'count' => $appointments[$dateString] ?? 0,
            ];
        }

        return $data;
    }

    public function getRevenueTrend(string $period = 'week'): array
    {
        $days = $period === 'week' ? 7 : 30;
        $startDate = now()->subDays($days - 1)->startOfDay();
        $endDate = now()->endOfDay();

        // Single query for all days
        $revenue = Payment::query()
            ->selectRaw('DATE(paid_at) as date, SUM(total) as amount')
            ->where('status', PaymentStatus::PAID)
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(paid_at)'))
            ->pluck('amount', 'date')
            ->toArray();

        // Build data array with all dates
        $data = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i);
            $dateString = $date->toDateString();

            $data[] = [
                'date' => $dateString,
                'day' => $date->translatedFormat('D'),
                'amount' => (float) ($revenue[$dateString] ?? 0),
            ];
        }

        return $data;
    }

    public function getStatusDistribution(): array
    {
        $thisMonth = now()->startOfMonth();

        // Single query for all status counts
        $stats = Appointment::query()
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as confirmed,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as cancelled,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as no_show
            ", [
                AppointmentStatus::PENDING->value,
                AppointmentStatus::CONFIRMED->value,
                AppointmentStatus::COMPLETED->value,
                AppointmentStatus::CANCELLED->value,
                AppointmentStatus::NO_SHOW->value,
            ])
            ->where('appointment_date', '>=', $thisMonth)
            ->first();

        $total = (int) $stats->total;

        if ($total === 0) {
            return [
                'pending' => 0,
                'confirmed' => 0,
                'completed' => 0,
                'cancelled' => 0,
                'no_show' => 0,
            ];
        }

        return [
            'pending' => round((int) $stats->pending / $total * 100, 1),
            'confirmed' => round((int) $stats->confirmed / $total * 100, 1),
            'completed' => round((int) $stats->completed / $total * 100, 1),
            'cancelled' => round((int) $stats->cancelled / $total * 100, 1),
            'no_show' => round((int) $stats->no_show / $total * 100, 1),
        ];
    }

    public function getPaymentMethodsDistribution(): array
    {
        $thisMonth = now()->startOfMonth();

        // Single query for all payment method totals
        $stats = Payment::query()
            ->selectRaw("
                COALESCE(SUM(total), 0) as total,
                COALESCE(SUM(CASE WHEN method = 'cash' THEN total ELSE 0 END), 0) as cash,
                COALESCE(SUM(CASE WHEN method = 'card' THEN total ELSE 0 END), 0) as card,
                COALESCE(SUM(CASE WHEN method = 'wallet' THEN total ELSE 0 END), 0) as wallet
            ")
            ->where('status', PaymentStatus::PAID)
            ->where('paid_at', '>=', $thisMonth)
            ->first();

        $total = (float) $stats->total;

        if ($total == 0) {
            return [
                'cash' => 0,
                'card' => 0,
                'wallet' => 0,
            ];
        }

        return [
            'cash' => round((float) $stats->cash / $total * 100, 1),
            'card' => round((float) $stats->card / $total * 100, 1),
            'wallet' => round((float) $stats->wallet / $total * 100, 1),
        ];
    }

    public function getRecentActivity(int $limit = 10): array
    {
        $recentAppointments = Appointment::with('patient')
            ->latest()
            ->take($limit)
            ->get()
            ->map(fn ($apt) => [
                'type' => 'appointment',
                'id' => $apt->id,
                'description' => "موعد جديد: {$apt->patient->name}",
                'status' => $apt->status->value,
                'date' => $apt->created_at->format('Y-m-d H:i'),
                'timestamp' => $apt->created_at->timestamp,
            ]);

        $recentPayments = Payment::with('appointment.patient')
            ->paid()
            ->latest('paid_at')
            ->take($limit)
            ->get()
            ->map(fn ($payment) => [
                'type' => 'payment',
                'id' => $payment->id,
                'description' => "دفعة: {$payment->formatted_total} - {$payment->appointment?->patient?->name}",
                'status' => $payment->status->value,
                'date' => $payment->paid_at->format('Y-m-d H:i'),
                'timestamp' => $payment->paid_at->timestamp,
            ]);

        $recentRecords = MedicalRecord::with('patient')
            ->latest()
            ->take($limit)
            ->get()
            ->map(fn ($record) => [
                'type' => 'medical_record',
                'id' => $record->id,
                'description' => "سجل طبي: {$record->patient->name}",
                'status' => 'created',
                'date' => $record->created_at->format('Y-m-d H:i'),
                'timestamp' => $record->created_at->timestamp,
            ]);

        return collect()
            ->merge($recentAppointments)
            ->merge($recentPayments)
            ->merge($recentRecords)
            ->sortByDesc('timestamp')
            ->take($limit)
            ->values()
            ->toArray();
    }

    public function getUpcomingAppointments(int $limit = 5): Collection
    {
        return Appointment::with('patient')
            ->upcoming()
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->take($limit)
            ->get();
    }

    private function getTotalPatients(): int
    {
        return User::where('role', 'patient')->count();
    }

    private function getTotalAppointments(): int
    {
        return Appointment::count();
    }

    private function getTotalRevenue(): float
    {
        return (float) Payment::paid()->sum('total');
    }

    private function getPendingAppointmentsCount(): int
    {
        return Appointment::where('status', AppointmentStatus::PENDING)->count();
    }

    private function getTodayAppointmentsCount(): int
    {
        return Appointment::whereDate('appointment_date', now()->toDateString())->count();
    }

    private function getThisWeekAppointmentsCount(): int
    {
        return Appointment::whereBetween('appointment_date', [
            now()->startOfWeek()->toDateString(),
            now()->endOfWeek()->toDateString(),
        ])->count();
    }

    private function getThisMonthAppointmentsCount(): int
    {
        return Appointment::whereMonth('appointment_date', now()->month)
            ->whereYear('appointment_date', now()->year)
            ->count();
    }

    private function getThisMonthRevenue(): float
    {
        return (float) Payment::paid()
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('total');
    }

    private function getNextAppointment(): ?array
    {
        $next = Appointment::with('patient')
            ->upcoming()
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->first();

        if (!$next) {
            return null;
        }

        return [
            'id' => $next->id,
            'patient_name' => $next->patient->name,
            'date' => $next->appointment_date->format('Y-m-d'),
            'time' => $next->formatted_time,
            'status' => $next->status->value,
        ];
    }
}
