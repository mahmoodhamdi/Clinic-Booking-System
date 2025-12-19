<?php

namespace Tests\Feature\Api\Admin;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\ClinicSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AppointmentApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        ClinicSetting::factory()->create();
    }

    /** @test */
    public function admin_can_list_all_appointments(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        Appointment::factory()->count(5)->create();

        $response = $this->getJson('/api/admin/appointments');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonCount(5, 'data');
    }

    /** @test */
    public function admin_can_filter_appointments_by_status(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        Appointment::factory()->pending()->count(3)->create();
        Appointment::factory()->confirmed()->count(2)->create();

        $response = $this->getJson('/api/admin/appointments?status=pending');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function admin_can_filter_appointments_by_date(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $date = now()->addDays(2)->toDateString();
        Appointment::factory()->forDate($date)->count(2)->create();
        Appointment::factory()->tomorrow()->create();

        $response = $this->getJson("/api/admin/appointments?date={$date}");

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function admin_can_filter_appointments_by_patient(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $patient = User::factory()->patient()->create();
        Appointment::factory()->forPatient($patient)->count(3)->create();
        Appointment::factory()->count(2)->create();

        $response = $this->getJson("/api/admin/appointments?patient_id={$patient->id}");

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function admin_can_get_today_appointments(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        Appointment::factory()->today()->count(3)->create();
        Appointment::factory()->tomorrow()->create();

        $response = $this->getJson('/api/admin/appointments/today');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'summary' => ['total', 'pending', 'confirmed', 'completed'],
            ]);
    }

    /** @test */
    public function admin_can_get_upcoming_appointments(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        Appointment::factory()
            ->pending()
            ->forDate(now()->addDays(3)->toDateString())
            ->count(2)
            ->create();

        Appointment::factory()
            ->completed()
            ->past()
            ->create();

        $response = $this->getJson('/api/admin/appointments/upcoming');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function admin_can_get_appointments_for_specific_date(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $date = now()->addDays(5)->toDateString();
        Appointment::factory()->forDate($date)->count(4)->create();

        $response = $this->getJson("/api/admin/appointments/for-date?date={$date}");

        $response->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonStructure([
                'summary' => ['date', 'day_name', 'total'],
            ]);
    }

    /** @test */
    public function admin_can_get_statistics(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        Appointment::factory()->pending()->count(2)->create();
        Appointment::factory()->confirmed()->count(3)->create();
        Appointment::factory()->completed()->count(5)->create();

        $response = $this->getJson('/api/admin/appointments/statistics');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'total',
                    'by_status',
                    'today',
                    'this_week',
                    'this_month',
                ],
            ]);

        $this->assertEquals(10, $response->json('data.total'));
    }

    /** @test */
    public function admin_can_view_single_appointment(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $appointment = Appointment::factory()->create();

        $response = $this->getJson("/api/admin/appointments/{$appointment->id}");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $appointment->id,
                ],
            ]);
    }

    /** @test */
    public function admin_can_confirm_appointment(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $appointment = Appointment::factory()->pending()->create();

        $response = $this->postJson("/api/admin/appointments/{$appointment->id}/confirm");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'confirmed',
                ],
            ]);

        $this->assertNotNull($appointment->fresh()->confirmed_at);
    }

    /** @test */
    public function admin_cannot_confirm_non_pending_appointment(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $appointment = Appointment::factory()->confirmed()->create();

        $response = $this->postJson("/api/admin/appointments/{$appointment->id}/confirm");

        $response->assertStatus(422);
    }

    /** @test */
    public function admin_can_complete_appointment(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $appointment = Appointment::factory()->confirmed()->create();

        $response = $this->postJson("/api/admin/appointments/{$appointment->id}/complete", [
            'admin_notes' => 'تم الكشف بنجاح',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'completed',
                    'admin_notes' => 'تم الكشف بنجاح',
                ],
            ]);
    }

    /** @test */
    public function admin_cannot_complete_pending_appointment(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $appointment = Appointment::factory()->pending()->create();

        $response = $this->postJson("/api/admin/appointments/{$appointment->id}/complete");

        $response->assertStatus(422);
    }

    /** @test */
    public function admin_can_cancel_appointment(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $appointment = Appointment::factory()->confirmed()->create();

        $response = $this->postJson("/api/admin/appointments/{$appointment->id}/cancel", [
            'reason' => 'إلغاء بسبب ظروف العيادة',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'cancelled',
                    'cancelled_by' => 'admin',
                ],
            ]);
    }

    /** @test */
    public function admin_can_mark_as_no_show(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $appointment = Appointment::factory()->confirmed()->create();

        $response = $this->postJson("/api/admin/appointments/{$appointment->id}/no-show");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'no_show',
                ],
            ]);
    }

    /** @test */
    public function admin_cannot_mark_pending_as_no_show(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $appointment = Appointment::factory()->pending()->create();

        $response = $this->postJson("/api/admin/appointments/{$appointment->id}/no-show");

        $response->assertStatus(422);
    }

    /** @test */
    public function admin_can_update_notes(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $appointment = Appointment::factory()->create();

        $response = $this->putJson("/api/admin/appointments/{$appointment->id}/notes", [
            'admin_notes' => 'ملاحظات جديدة',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'admin_notes' => 'ملاحظات جديدة',
                ],
            ]);
    }

    /** @test */
    public function non_admin_cannot_access_admin_appointments(): void
    {
        $patient = User::factory()->patient()->create();
        Sanctum::actingAs($patient);

        $response = $this->getJson('/api/admin/appointments');

        $response->assertForbidden();
    }

    /** @test */
    public function unauthenticated_user_cannot_access_admin_appointments(): void
    {
        $response = $this->getJson('/api/admin/appointments');

        $response->assertUnauthorized();
    }

    /** @test */
    public function secretary_can_access_admin_appointments(): void
    {
        $secretary = User::factory()->secretary()->create();
        Sanctum::actingAs($secretary);

        Appointment::factory()->count(2)->create();

        $response = $this->getJson('/api/admin/appointments');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }
}
