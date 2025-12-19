<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardTest extends TestCase
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

    public function test_admin_can_get_dashboard_stats(): void
    {
        User::factory()->count(5)->patient()->create();
        Appointment::factory()->count(10)->create();
        Payment::factory()->count(5)->paid()->create(['total' => 100.00]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/admin/dashboard/stats');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'total_patients',
                    'total_appointments',
                    'total_revenue',
                    'pending_appointments',
                    'today_appointments',
                    'this_week_appointments',
                    'this_month_revenue',
                    'this_month_appointments',
                ],
            ]);
    }

    public function test_admin_can_get_today_statistics(): void
    {
        $today = now()->toDateString();

        Appointment::factory()->count(5)->create([
            'appointment_date' => $today,
        ]);
        Payment::factory()->count(3)->paid()->create([
            'created_at' => now(),
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/admin/dashboard/today');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'appointments' => [
                        'total',
                        'pending',
                        'confirmed',
                        'completed',
                        'cancelled',
                        'no_show',
                    ],
                    'revenue' => [
                        'total',
                        'paid',
                        'pending',
                    ],
                    'next_appointment',
                ],
            ]);
    }

    public function test_admin_can_get_weekly_statistics(): void
    {
        $startOfWeek = now()->startOfWeek();

        Appointment::factory()->count(8)->create([
            'appointment_date' => $startOfWeek->copy()->addDays(2)->toDateString(),
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/admin/dashboard/weekly');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'appointments',
                    'completed',
                    'revenue',
                    'new_patients',
                ],
            ]);
    }

    public function test_admin_can_get_monthly_statistics(): void
    {
        Appointment::factory()->count(15)->create([
            'appointment_date' => now()->toDateString(),
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/admin/dashboard/monthly');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'appointments',
                    'completed',
                    'cancelled',
                    'revenue',
                    'new_patients',
                    'average_daily_appointments',
                ],
            ]);
    }

    public function test_admin_can_get_monthly_statistics_for_specific_month(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/admin/dashboard/monthly?month=6&year=2025');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'appointments',
                    'completed',
                    'cancelled',
                    'revenue',
                    'new_patients',
                    'average_daily_appointments',
                ],
            ]);
    }

    public function test_admin_can_get_chart_data(): void
    {
        Appointment::factory()->count(5)->create([
            'appointment_date' => now()->subDays(2)->toDateString(),
        ]);
        Payment::factory()->count(3)->paid()->create([
            'paid_at' => now()->subDays(1),
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/admin/dashboard/chart');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'appointments_trend',
                    'revenue_trend',
                    'status_distribution',
                    'payment_methods',
                ],
            ]);
    }

    public function test_admin_can_get_chart_data_for_month(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/admin/dashboard/chart?period=month');

        $response->assertOk();

        $data = $response->json('data');
        $this->assertCount(30, $data['appointments_trend']);
        $this->assertCount(30, $data['revenue_trend']);
    }

    public function test_admin_can_get_recent_activity(): void
    {
        Appointment::factory()->count(5)->create();
        Payment::factory()->count(3)->paid()->create();

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/admin/dashboard/recent-activity');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'type',
                        'id',
                        'description',
                        'status',
                        'date',
                    ],
                ],
            ]);
    }

    public function test_admin_can_limit_recent_activity(): void
    {
        Appointment::factory()->count(20)->create();

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/admin/dashboard/recent-activity?limit=5');

        $response->assertOk();
        $this->assertLessThanOrEqual(5, count($response->json('data')));
    }

    public function test_admin_can_get_upcoming_appointments(): void
    {
        Appointment::factory()->count(5)->create([
            'appointment_date' => now()->addDays(1)->toDateString(),
            'status' => AppointmentStatus::CONFIRMED,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/admin/dashboard/upcoming-appointments');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'date',
                        'time',
                        'status',
                    ],
                ],
            ]);
    }

    public function test_patient_cannot_access_dashboard(): void
    {
        Sanctum::actingAs($this->patient);

        $this->getJson('/api/admin/dashboard/stats')->assertForbidden();
        $this->getJson('/api/admin/dashboard/today')->assertForbidden();
        $this->getJson('/api/admin/dashboard/chart')->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_access_dashboard(): void
    {
        $this->getJson('/api/admin/dashboard/stats')->assertUnauthorized();
        $this->getJson('/api/admin/dashboard/today')->assertUnauthorized();
        $this->getJson('/api/admin/dashboard/chart')->assertUnauthorized();
    }
}
