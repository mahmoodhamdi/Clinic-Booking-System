<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->patient = User::factory()->create();
    }

    public function test_admin_can_get_appointments_report(): void
    {
        Appointment::factory()->count(10)->create([
            'appointment_date' => now()->toDateString(),
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/admin/reports/appointments');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'period' => ['from', 'to'],
                    'summary' => [
                        'total',
                        'completed',
                        'cancelled',
                        'no_show',
                        'pending',
                        'confirmed',
                    ],
                    'completion_rate',
                    'cancellation_rate',
                    'appointments',
                ],
            ]);
    }

    public function test_admin_can_filter_appointments_report_by_date(): void
    {
        Appointment::factory()->count(5)->create([
            'appointment_date' => now()->toDateString(),
        ]);
        Appointment::factory()->count(3)->create([
            'appointment_date' => now()->subMonth()->toDateString(),
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/admin/reports/appointments?' . http_build_query([
            'from_date' => now()->startOfMonth()->toDateString(),
            'to_date' => now()->endOfMonth()->toDateString(),
        ]));

        $response->assertOk();
        $this->assertEquals(5, $response->json('data.summary.total'));
    }

    public function test_admin_can_filter_appointments_report_by_status(): void
    {
        Appointment::factory()->count(5)->create([
            'appointment_date' => now()->toDateString(),
            'status' => AppointmentStatus::COMPLETED,
        ]);
        Appointment::factory()->count(3)->create([
            'appointment_date' => now()->toDateString(),
            'status' => AppointmentStatus::CANCELLED,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/admin/reports/appointments?status=completed');

        $response->assertOk();
        $this->assertCount(5, $response->json('data.appointments'));
    }

    public function test_admin_can_get_revenue_report(): void
    {
        Payment::factory()->count(10)->paid()->create([
            'total' => 100.00,
            'paid_at' => now(),
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/admin/reports/revenue');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'period' => ['from', 'to'],
                    'summary' => [
                        'total_revenue',
                        'total_discount',
                        'total_payments',
                        'average_payment',
                    ],
                    'by_method',
                    'breakdown',
                    'payments',
                ],
            ]);
    }

    public function test_admin_can_filter_revenue_report_by_date(): void
    {
        Payment::factory()->count(5)->paid()->create([
            'total' => 100.00,
            'paid_at' => now(),
        ]);
        Payment::factory()->count(3)->paid()->create([
            'total' => 100.00,
            'paid_at' => now()->subMonth(),
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/admin/reports/revenue?' . http_build_query([
            'from_date' => now()->startOfMonth()->toDateString(),
            'to_date' => now()->endOfMonth()->toDateString(),
        ]));

        $response->assertOk();
        $this->assertEquals(5, $response->json('data.summary.total_payments'));
    }

    public function test_admin_can_group_revenue_report_by_week(): void
    {
        Payment::factory()->count(10)->paid()->create([
            'paid_at' => now(),
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/admin/reports/revenue?group_by=week');

        $response->assertOk();
        $this->assertArrayHasKey('breakdown', $response->json('data'));
    }

    public function test_admin_can_group_revenue_report_by_month(): void
    {
        Payment::factory()->count(10)->paid()->create([
            'paid_at' => now(),
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/admin/reports/revenue?group_by=month');

        $response->assertOk();
        $this->assertArrayHasKey('breakdown', $response->json('data'));
    }

    public function test_admin_can_get_patients_report(): void
    {
        $patient = User::factory()->patient()->create();
        Appointment::factory()->count(5)->create([
            'user_id' => $patient->id,
            'status' => AppointmentStatus::COMPLETED,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/admin/reports/patients');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'period' => ['from', 'to'],
                    'summary' => [
                        'total_patients',
                        'active_patients',
                        'inactive_patients',
                    ],
                    'patients',
                ],
            ]);
    }

    public function test_admin_can_filter_patients_report_by_date(): void
    {
        User::factory()->count(5)->patient()->create([
            'created_at' => now(),
        ]);
        User::factory()->count(3)->patient()->create([
            'created_at' => now()->subYear()->subDay(),
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/admin/reports/patients?' . http_build_query([
            'from_date' => now()->startOfYear()->toDateString(),
            'to_date' => now()->endOfYear()->toDateString(),
        ]));

        $response->assertOk();
        // 5 created here + 1 from setUp() = 6 patients this year
        $this->assertEquals(6, $response->json('data.summary.total_patients'));
    }

    public function test_admin_can_export_appointments_report_to_pdf(): void
    {
        Appointment::factory()->count(5)->create([
            'appointment_date' => now()->toDateString(),
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->get('/api/admin/reports/appointments/export');

        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_admin_can_export_revenue_report_to_pdf(): void
    {
        Payment::factory()->count(5)->paid()->create([
            'paid_at' => now(),
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->get('/api/admin/reports/revenue/export');

        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_admin_can_export_patients_report_to_pdf(): void
    {
        User::factory()->count(5)->patient()->create();

        Sanctum::actingAs($this->admin);

        $response = $this->get('/api/admin/reports/patients/export');

        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_patient_cannot_access_reports(): void
    {
        Sanctum::actingAs($this->patient);

        $this->getJson('/api/admin/reports/appointments')->assertForbidden();
        $this->getJson('/api/admin/reports/revenue')->assertForbidden();
        $this->getJson('/api/admin/reports/patients')->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_access_reports(): void
    {
        $this->getJson('/api/admin/reports/appointments')->assertUnauthorized();
        $this->getJson('/api/admin/reports/revenue')->assertUnauthorized();
        $this->getJson('/api/admin/reports/patients')->assertUnauthorized();
    }

    public function test_appointments_report_includes_patient_details(): void
    {
        Appointment::factory()->create([
            'appointment_date' => now()->toDateString(),
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/admin/reports/appointments');

        $response->assertOk();
        $appointment = $response->json('data.appointments.0');

        $this->assertArrayHasKey('patient_name', $appointment);
        $this->assertArrayHasKey('patient_phone', $appointment);
        $this->assertArrayHasKey('date', $appointment);
        $this->assertArrayHasKey('time', $appointment);
        $this->assertArrayHasKey('status', $appointment);
        $this->assertArrayHasKey('status_label', $appointment);
    }

    public function test_revenue_report_includes_payment_details(): void
    {
        Payment::factory()->paid()->create([
            'paid_at' => now(),
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/admin/reports/revenue');

        $response->assertOk();
        $payment = $response->json('data.payments.0');

        $this->assertArrayHasKey('amount', $payment);
        $this->assertArrayHasKey('discount', $payment);
        $this->assertArrayHasKey('total', $payment);
        $this->assertArrayHasKey('method', $payment);
        $this->assertArrayHasKey('paid_at', $payment);
    }

    public function test_patients_report_includes_appointment_counts(): void
    {
        $patient = User::factory()->patient()->create();
        Appointment::factory()->count(5)->create([
            'user_id' => $patient->id,
            'status' => AppointmentStatus::COMPLETED,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/admin/reports/patients');

        $response->assertOk();

        // Find the patient with appointments in the response
        $patients = collect($response->json('data.patients'));
        $patientData = $patients->firstWhere('id', $patient->id);

        $this->assertNotNull($patientData);
        $this->assertArrayHasKey('total_appointments', $patientData);
        $this->assertArrayHasKey('completed_appointments', $patientData);
        $this->assertEquals(5, $patientData['total_appointments']);
        $this->assertEquals(5, $patientData['completed_appointments']);
    }
}
