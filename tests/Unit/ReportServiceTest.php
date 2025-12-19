<?php

namespace Tests\Unit;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Payment;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReportService $reportService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reportService = app(ReportService::class);
    }

    public function test_get_appointments_report(): void
    {
        $fromDate = now()->startOfMonth()->toDateString();
        $toDate = now()->endOfMonth()->toDateString();

        // Create appointments
        Appointment::factory()->count(5)->create([
            'appointment_date' => now()->toDateString(),
            'status' => AppointmentStatus::COMPLETED,
        ]);
        Appointment::factory()->count(3)->create([
            'appointment_date' => now()->toDateString(),
            'status' => AppointmentStatus::CANCELLED,
        ]);

        $report = $this->reportService->getAppointmentsReport($fromDate, $toDate);

        $this->assertArrayHasKey('period', $report);
        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('completion_rate', $report);
        $this->assertArrayHasKey('cancellation_rate', $report);
        $this->assertArrayHasKey('appointments', $report);

        $this->assertEquals(8, $report['summary']['total']);
        $this->assertEquals(5, $report['summary']['completed']);
        $this->assertEquals(3, $report['summary']['cancelled']);
        $this->assertEquals(62.5, $report['completion_rate']);
        $this->assertEquals(37.5, $report['cancellation_rate']);
    }

    public function test_get_appointments_report_with_status_filter(): void
    {
        // Create appointments with different statuses
        Appointment::factory()->count(5)->create([
            'appointment_date' => now()->toDateString(),
            'status' => AppointmentStatus::COMPLETED,
        ]);
        Appointment::factory()->count(3)->create([
            'appointment_date' => now()->toDateString(),
            'status' => AppointmentStatus::CANCELLED,
        ]);

        $report = $this->reportService->getAppointmentsReport(
            now()->startOfMonth()->toDateString(),
            now()->endOfMonth()->toDateString(),
            AppointmentStatus::COMPLETED->value
        );

        $this->assertCount(5, $report['appointments']);
    }

    public function test_get_revenue_report(): void
    {
        $fromDate = now()->startOfMonth()->toDateString();
        $toDate = now()->endOfMonth()->toDateString();

        // Create payments
        Payment::factory()->count(5)->paid()->cash()->create([
            'amount' => 100.00,
            'discount' => 10.00,
            'total' => 90.00,
            'paid_at' => now(),
        ]);
        Payment::factory()->count(3)->paid()->card()->create([
            'amount' => 150.00,
            'discount' => 0.00,
            'total' => 150.00,
            'paid_at' => now(),
        ]);

        $report = $this->reportService->getRevenueReport($fromDate, $toDate);

        $this->assertArrayHasKey('period', $report);
        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('by_method', $report);
        $this->assertArrayHasKey('breakdown', $report);
        $this->assertArrayHasKey('payments', $report);

        $this->assertEquals(900.00, $report['summary']['total_revenue']);
        $this->assertEquals(50.00, $report['summary']['total_discount']);
        $this->assertEquals(8, $report['summary']['total_payments']);
        $this->assertEquals(112.50, $report['summary']['average_payment']);

        $this->assertEquals(450.00, $report['by_method']['cash']);
        $this->assertEquals(450.00, $report['by_method']['card']);
    }

    public function test_get_revenue_report_grouped_by_day(): void
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

        $report = $this->reportService->getRevenueReport(
            now()->subDays(5)->toDateString(),
            now()->toDateString(),
            'day'
        );

        $this->assertArrayHasKey('breakdown', $report);
        $this->assertNotEmpty($report['breakdown']);
    }

    public function test_get_revenue_report_grouped_by_week(): void
    {
        Payment::factory()->count(5)->paid()->create([
            'total' => 100.00,
            'paid_at' => now()->startOfMonth()->addDays(5),
        ]);

        $report = $this->reportService->getRevenueReport(
            now()->startOfMonth()->toDateString(),
            now()->endOfMonth()->toDateString(),
            'week'
        );

        $this->assertArrayHasKey('breakdown', $report);
    }

    public function test_get_revenue_report_grouped_by_month(): void
    {
        Payment::factory()->count(5)->paid()->create([
            'total' => 100.00,
            'paid_at' => now(),
        ]);

        $report = $this->reportService->getRevenueReport(
            now()->startOfYear()->toDateString(),
            now()->endOfYear()->toDateString(),
            'month'
        );

        $this->assertArrayHasKey('breakdown', $report);
    }

    public function test_get_patients_report(): void
    {
        $fromDate = now()->startOfYear()->toDateString();
        $toDate = now()->endOfYear()->toDateString();

        // Create patients with appointments
        $patient1 = User::factory()->patient()->create();
        $patient2 = User::factory()->patient()->create();

        Appointment::factory()->count(5)->create([
            'user_id' => $patient1->id,
            'status' => AppointmentStatus::COMPLETED,
        ]);
        Appointment::factory()->count(3)->create([
            'user_id' => $patient2->id,
            'status' => AppointmentStatus::PENDING,
        ]);

        $report = $this->reportService->getPatientsReport($fromDate, $toDate);

        $this->assertArrayHasKey('period', $report);
        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('patients', $report);

        $this->assertEquals(2, $report['summary']['total_patients']);
        $this->assertEquals(1, $report['summary']['active_patients']);
        $this->assertEquals(1, $report['summary']['inactive_patients']);
    }

    public function test_appointments_report_has_correct_structure(): void
    {
        Appointment::factory()->create([
            'appointment_date' => now()->toDateString(),
        ]);

        $report = $this->reportService->getAppointmentsReport();

        $this->assertNotEmpty($report['appointments']);
        $appointment = $report['appointments'][0];

        $this->assertArrayHasKey('id', $appointment);
        $this->assertArrayHasKey('patient_name', $appointment);
        $this->assertArrayHasKey('patient_phone', $appointment);
        $this->assertArrayHasKey('date', $appointment);
        $this->assertArrayHasKey('time', $appointment);
        $this->assertArrayHasKey('status', $appointment);
        $this->assertArrayHasKey('status_label', $appointment);
    }

    public function test_revenue_report_payments_have_correct_structure(): void
    {
        Payment::factory()->paid()->create([
            'paid_at' => now(),
        ]);

        $report = $this->reportService->getRevenueReport();

        $this->assertNotEmpty($report['payments']);
        $payment = $report['payments'][0];

        $this->assertArrayHasKey('id', $payment);
        $this->assertArrayHasKey('patient_name', $payment);
        $this->assertArrayHasKey('amount', $payment);
        $this->assertArrayHasKey('discount', $payment);
        $this->assertArrayHasKey('total', $payment);
        $this->assertArrayHasKey('method', $payment);
        $this->assertArrayHasKey('method_label', $payment);
        $this->assertArrayHasKey('paid_at', $payment);
    }

    public function test_patients_report_has_correct_structure(): void
    {
        $patient = User::factory()->patient()->create();
        Appointment::factory()->count(3)->create([
            'user_id' => $patient->id,
            'status' => AppointmentStatus::COMPLETED,
        ]);

        $report = $this->reportService->getPatientsReport();

        $this->assertNotEmpty($report['patients']);
        $patientData = $report['patients'][0];

        $this->assertArrayHasKey('id', $patientData);
        $this->assertArrayHasKey('name', $patientData);
        $this->assertArrayHasKey('phone', $patientData);
        $this->assertArrayHasKey('email', $patientData);
        $this->assertArrayHasKey('registered_at', $patientData);
        $this->assertArrayHasKey('total_appointments', $patientData);
        $this->assertArrayHasKey('completed_appointments', $patientData);
    }

    public function test_empty_appointments_report(): void
    {
        $report = $this->reportService->getAppointmentsReport(
            now()->subMonth()->toDateString(),
            now()->toDateString()
        );

        $this->assertEquals(0, $report['summary']['total']);
        $this->assertEmpty($report['appointments']);
        $this->assertEquals(0, $report['completion_rate']);
    }

    public function test_empty_revenue_report(): void
    {
        $report = $this->reportService->getRevenueReport(
            now()->subMonth()->toDateString(),
            now()->toDateString()
        );

        $this->assertEquals(0.0, $report['summary']['total_revenue']);
        $this->assertEquals(0, $report['summary']['total_payments']);
        $this->assertEmpty($report['payments']);
    }

    public function test_empty_patients_report(): void
    {
        $report = $this->reportService->getPatientsReport(
            now()->subMonth()->toDateString(),
            now()->toDateString()
        );

        $this->assertEquals(0, $report['summary']['total_patients']);
        $this->assertEmpty($report['patients']);
    }
}
