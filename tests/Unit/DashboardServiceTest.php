<?php

namespace Tests\Unit;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentStatus;
use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\Payment;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    private DashboardService $dashboardService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dashboardService = app(DashboardService::class);
    }

    public function test_get_overview_statistics(): void
    {
        // Create a patient to reuse
        $patient = User::factory()->patient()->create();

        // Create appointments for the same patient
        $appointments = Appointment::factory()->count(10)->create(['user_id' => $patient->id]);
        Appointment::factory()->count(3)->create([
            'user_id' => $patient->id,
            'status' => AppointmentStatus::PENDING,
        ]);

        // Create payments for existing appointments (to avoid creating more appointments)
        Payment::factory()->count(5)->paid()->create([
            'total' => 100.00,
            'appointment_id' => $appointments->random()->id,
        ]);

        $stats = $this->dashboardService->getOverviewStatistics();

        $this->assertArrayHasKey('total_patients', $stats);
        $this->assertArrayHasKey('total_appointments', $stats);
        $this->assertArrayHasKey('total_revenue', $stats);
        $this->assertArrayHasKey('pending_appointments', $stats);
        $this->assertArrayHasKey('today_appointments', $stats);
        $this->assertArrayHasKey('this_week_appointments', $stats);
        $this->assertArrayHasKey('this_month_revenue', $stats);
        $this->assertArrayHasKey('this_month_appointments', $stats);

        $this->assertGreaterThanOrEqual(1, $stats['total_patients']);
        $this->assertEquals(13, $stats['total_appointments']);
        $this->assertEquals(500.00, $stats['total_revenue']);
    }

    public function test_get_today_statistics(): void
    {
        $today = now()->toDateString();

        // Create today's appointments
        Appointment::factory()->count(3)->create([
            'appointment_date' => $today,
            'status' => AppointmentStatus::PENDING,
        ]);
        Appointment::factory()->count(2)->create([
            'appointment_date' => $today,
            'status' => AppointmentStatus::COMPLETED,
        ]);

        // Create today's payments
        Payment::factory()->count(2)->paid()->create([
            'total' => 150.00,
            'created_at' => now(),
        ]);

        $stats = $this->dashboardService->getTodayStatistics();

        $this->assertArrayHasKey('appointments', $stats);
        $this->assertArrayHasKey('revenue', $stats);
        $this->assertArrayHasKey('next_appointment', $stats);

        $this->assertEquals(5, $stats['appointments']['total']);
        $this->assertEquals(3, $stats['appointments']['pending']);
        $this->assertEquals(2, $stats['appointments']['completed']);
    }

    public function test_get_weekly_statistics(): void
    {
        $startOfWeek = now()->startOfWeek();

        // Create a patient to reuse for appointments
        $patient = User::factory()->patient()->create([
            'created_at' => $startOfWeek->copy()->addDays(1),
        ]);

        // Create this week's appointments with the same patient
        Appointment::factory()->count(5)->create([
            'user_id' => $patient->id,
            'appointment_date' => $startOfWeek->copy()->addDays(2)->toDateString(),
        ]);

        // Create this week's payments
        Payment::factory()->count(3)->paid()->create([
            'total' => 200.00,
            'paid_at' => $startOfWeek->copy()->addDays(1),
        ]);

        // Create one more new patient this week
        User::factory()->patient()->create([
            'created_at' => $startOfWeek->copy()->addDays(1),
        ]);

        $stats = $this->dashboardService->getWeeklyStatistics();

        $this->assertArrayHasKey('appointments', $stats);
        $this->assertArrayHasKey('completed', $stats);
        $this->assertArrayHasKey('revenue', $stats);
        $this->assertArrayHasKey('new_patients', $stats);

        $this->assertEquals(5, $stats['appointments']);
        $this->assertEquals(600.00, $stats['revenue']);
        // At least 2 patients were created this week
        $this->assertGreaterThanOrEqual(2, $stats['new_patients']);
    }

    public function test_get_monthly_statistics(): void
    {
        $startOfMonth = now()->startOfMonth();

        // Create this month's appointments
        Appointment::factory()->count(20)->create([
            'appointment_date' => $startOfMonth->copy()->addDays(5)->toDateString(),
        ]);

        $stats = $this->dashboardService->getMonthlyStatistics();

        $this->assertArrayHasKey('appointments', $stats);
        $this->assertArrayHasKey('completed', $stats);
        $this->assertArrayHasKey('cancelled', $stats);
        $this->assertArrayHasKey('revenue', $stats);
        $this->assertArrayHasKey('new_patients', $stats);
        $this->assertArrayHasKey('average_daily_appointments', $stats);
    }

    public function test_get_chart_data(): void
    {
        // Create some appointments and payments
        Appointment::factory()->count(5)->create([
            'appointment_date' => now()->subDays(2)->toDateString(),
        ]);

        Payment::factory()->count(3)->paid()->create([
            'paid_at' => now()->subDays(1),
        ]);

        $chartData = $this->dashboardService->getChartData('week');

        $this->assertArrayHasKey('appointments_trend', $chartData);
        $this->assertArrayHasKey('revenue_trend', $chartData);
        $this->assertArrayHasKey('status_distribution', $chartData);
        $this->assertArrayHasKey('payment_methods', $chartData);

        $this->assertCount(7, $chartData['appointments_trend']);
        $this->assertCount(7, $chartData['revenue_trend']);
    }

    public function test_get_appointments_trend(): void
    {
        // Create appointments for different days
        Appointment::factory()->count(3)->create([
            'appointment_date' => now()->subDays(2)->toDateString(),
        ]);
        Appointment::factory()->count(5)->create([
            'appointment_date' => now()->subDays(1)->toDateString(),
        ]);

        $trend = $this->dashboardService->getAppointmentsTrend('week');

        $this->assertCount(7, $trend);
        $this->assertArrayHasKey('date', $trend[0]);
        $this->assertArrayHasKey('day', $trend[0]);
        $this->assertArrayHasKey('count', $trend[0]);
    }

    public function test_get_revenue_trend(): void
    {
        // Create payments for different days
        Payment::factory()->count(2)->paid()->create([
            'total' => 100.00,
            'paid_at' => now()->subDays(2),
        ]);
        Payment::factory()->count(3)->paid()->create([
            'total' => 150.00,
            'paid_at' => now()->subDays(1),
        ]);

        $trend = $this->dashboardService->getRevenueTrend('week');

        $this->assertCount(7, $trend);
        $this->assertArrayHasKey('date', $trend[0]);
        $this->assertArrayHasKey('day', $trend[0]);
        $this->assertArrayHasKey('amount', $trend[0]);
    }

    public function test_get_status_distribution(): void
    {
        $thisMonth = now()->startOfMonth();

        Appointment::factory()->count(8)->create([
            'appointment_date' => $thisMonth->copy()->addDays(5)->toDateString(),
            'status' => AppointmentStatus::COMPLETED,
        ]);
        Appointment::factory()->count(2)->create([
            'appointment_date' => $thisMonth->copy()->addDays(5)->toDateString(),
            'status' => AppointmentStatus::CANCELLED,
        ]);

        $distribution = $this->dashboardService->getStatusDistribution();

        $this->assertArrayHasKey('pending', $distribution);
        $this->assertArrayHasKey('confirmed', $distribution);
        $this->assertArrayHasKey('completed', $distribution);
        $this->assertArrayHasKey('cancelled', $distribution);
        $this->assertArrayHasKey('no_show', $distribution);

        $this->assertEquals(80.0, $distribution['completed']);
        $this->assertEquals(20.0, $distribution['cancelled']);
    }

    public function test_get_payment_methods_distribution(): void
    {
        $thisMonth = now()->startOfMonth();

        Payment::factory()->count(5)->paid()->cash()->create([
            'total' => 100.00,
            'paid_at' => $thisMonth->copy()->addDays(5),
        ]);
        Payment::factory()->count(3)->paid()->card()->create([
            'total' => 100.00,
            'paid_at' => $thisMonth->copy()->addDays(5),
        ]);
        Payment::factory()->count(2)->paid()->wallet()->create([
            'total' => 100.00,
            'paid_at' => $thisMonth->copy()->addDays(5),
        ]);

        $distribution = $this->dashboardService->getPaymentMethodsDistribution();

        $this->assertArrayHasKey('cash', $distribution);
        $this->assertArrayHasKey('card', $distribution);
        $this->assertArrayHasKey('wallet', $distribution);

        $this->assertEquals(50.0, $distribution['cash']);
        $this->assertEquals(30.0, $distribution['card']);
        $this->assertEquals(20.0, $distribution['wallet']);
    }

    public function test_get_recent_activity(): void
    {
        // Create recent appointments
        Appointment::factory()->count(3)->create();

        // Create recent payments
        Payment::factory()->count(2)->paid()->create();

        // Create recent medical records
        MedicalRecord::factory()->count(2)->create();

        $activity = $this->dashboardService->getRecentActivity(10);

        $this->assertIsArray($activity);
        $this->assertLessThanOrEqual(10, count($activity));

        if (count($activity) > 0) {
            $this->assertArrayHasKey('type', $activity[0]);
            $this->assertArrayHasKey('id', $activity[0]);
            $this->assertArrayHasKey('description', $activity[0]);
            $this->assertArrayHasKey('date', $activity[0]);
        }
    }

    public function test_get_upcoming_appointments(): void
    {
        // Create upcoming appointments
        Appointment::factory()->count(3)->create([
            'appointment_date' => now()->addDays(1)->toDateString(),
            'status' => AppointmentStatus::CONFIRMED,
        ]);
        Appointment::factory()->count(2)->create([
            'appointment_date' => now()->addDays(2)->toDateString(),
            'status' => AppointmentStatus::PENDING,
        ]);

        $upcoming = $this->dashboardService->getUpcomingAppointments(5);

        $this->assertCount(5, $upcoming);
    }

    public function test_status_distribution_returns_zeros_when_no_appointments(): void
    {
        $distribution = $this->dashboardService->getStatusDistribution();

        $this->assertEquals(0, $distribution['pending']);
        $this->assertEquals(0, $distribution['confirmed']);
        $this->assertEquals(0, $distribution['completed']);
        $this->assertEquals(0, $distribution['cancelled']);
        $this->assertEquals(0, $distribution['no_show']);
    }

    public function test_payment_methods_distribution_returns_zeros_when_no_payments(): void
    {
        $distribution = $this->dashboardService->getPaymentMethodsDistribution();

        $this->assertEquals(0, $distribution['cash']);
        $this->assertEquals(0, $distribution['card']);
        $this->assertEquals(0, $distribution['wallet']);
    }
}
