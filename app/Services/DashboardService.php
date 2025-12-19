<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentStatus;
use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DashboardService
{
    public function getOverviewStatistics(): array
    {
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
    }

    public function getTodayStatistics(): array
    {
        $today = now()->toDateString();

        $appointments = Appointment::whereDate('appointment_date', $today)->get();
        $payments = Payment::whereDate('created_at', $today)->get();

        return [
            'appointments' => [
                'total' => $appointments->count(),
                'pending' => $appointments->where('status', AppointmentStatus::PENDING)->count(),
                'confirmed' => $appointments->where('status', AppointmentStatus::CONFIRMED)->count(),
                'completed' => $appointments->where('status', AppointmentStatus::COMPLETED)->count(),
                'cancelled' => $appointments->where('status', AppointmentStatus::CANCELLED)->count(),
                'no_show' => $appointments->where('status', AppointmentStatus::NO_SHOW)->count(),
            ],
            'revenue' => [
                'total' => $payments->sum('total'),
                'paid' => $payments->where('status', PaymentStatus::PAID)->sum('total'),
                'pending' => $payments->where('status', PaymentStatus::PENDING)->sum('total'),
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
        return [
            'appointments_trend' => $this->getAppointmentsTrend($period),
            'revenue_trend' => $this->getRevenueTrend($period),
            'status_distribution' => $this->getStatusDistribution(),
            'payment_methods' => $this->getPaymentMethodsDistribution(),
        ];
    }

    public function getAppointmentsTrend(string $period = 'week'): array
    {
        $data = [];
        $days = $period === 'week' ? 7 : 30;
        $startDate = now()->subDays($days - 1)->startOfDay();

        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i);
            $count = Appointment::whereDate('appointment_date', $date->toDateString())->count();

            $data[] = [
                'date' => $date->toDateString(),
                'day' => $date->translatedFormat('D'),
                'count' => $count,
            ];
        }

        return $data;
    }

    public function getRevenueTrend(string $period = 'week'): array
    {
        $data = [];
        $days = $period === 'week' ? 7 : 30;
        $startDate = now()->subDays($days - 1)->startOfDay();

        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i);
            $amount = Payment::paid()
                ->whereDate('paid_at', $date->toDateString())
                ->sum('total');

            $data[] = [
                'date' => $date->toDateString(),
                'day' => $date->translatedFormat('D'),
                'amount' => (float) $amount,
            ];
        }

        return $data;
    }

    public function getStatusDistribution(): array
    {
        $thisMonth = now()->startOfMonth();

        $appointments = Appointment::where('appointment_date', '>=', $thisMonth)->get();
        $total = $appointments->count();

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
            'pending' => round($appointments->where('status', AppointmentStatus::PENDING)->count() / $total * 100, 1),
            'confirmed' => round($appointments->where('status', AppointmentStatus::CONFIRMED)->count() / $total * 100, 1),
            'completed' => round($appointments->where('status', AppointmentStatus::COMPLETED)->count() / $total * 100, 1),
            'cancelled' => round($appointments->where('status', AppointmentStatus::CANCELLED)->count() / $total * 100, 1),
            'no_show' => round($appointments->where('status', AppointmentStatus::NO_SHOW)->count() / $total * 100, 1),
        ];
    }

    public function getPaymentMethodsDistribution(): array
    {
        $thisMonth = now()->startOfMonth();

        $payments = Payment::paid()
            ->where('paid_at', '>=', $thisMonth)
            ->get();

        $total = $payments->sum('total');

        if ($total == 0) {
            return [
                'cash' => 0,
                'card' => 0,
                'wallet' => 0,
            ];
        }

        return [
            'cash' => round($payments->where('method', 'cash')->sum('total') / $total * 100, 1),
            'card' => round($payments->where('method', 'card')->sum('total') / $total * 100, 1),
            'wallet' => round($payments->where('method', 'wallet')->sum('total') / $total * 100, 1),
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
