<?php

namespace Tests\Feature\Api\Admin;

use App\Enums\DayOfWeek;
use App\Models\ClinicSetting;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ScheduleApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        ClinicSetting::factory()->create();
    }

    /** @test */
    public function admin_can_list_schedules(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        Schedule::factory()->forDay(DayOfWeek::SUNDAY)->create();
        Schedule::factory()->forDay(DayOfWeek::MONDAY)->create();

        $response = $this->getJson('/api/admin/schedules');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function admin_can_create_schedule(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/admin/schedules', [
            'day_of_week' => DayOfWeek::SUNDAY->value,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'is_active' => true,
        ]);

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'data' => [
                    'day_of_week' => DayOfWeek::SUNDAY->value,
                    'start_time' => '09:00',
                    'end_time' => '17:00',
                ],
            ]);

        $this->assertDatabaseHas('schedules', [
            'day_of_week' => DayOfWeek::SUNDAY->value,
        ]);
    }

    /** @test */
    public function admin_can_create_schedule_with_break(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/admin/schedules', [
            'day_of_week' => DayOfWeek::SUNDAY->value,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'break_start' => '13:00',
            'break_end' => '14:00',
        ]);

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'data' => [
                    'break_start' => '13:00',
                    'break_end' => '14:00',
                    'has_break' => true,
                ],
            ]);
    }

    /** @test */
    public function admin_can_update_schedule(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $schedule = Schedule::factory()->forDay(DayOfWeek::SUNDAY)->create([
            'start_time' => '09:00',
            'end_time' => '17:00',
        ]);

        $response = $this->putJson("/api/admin/schedules/{$schedule->id}", [
            'start_time' => '08:00',
            'end_time' => '18:00',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'start_time' => '08:00',
                    'end_time' => '18:00',
                ],
            ]);
    }

    /** @test */
    public function admin_can_delete_schedule(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $schedule = Schedule::factory()->forDay(DayOfWeek::SUNDAY)->create();

        $response = $this->deleteJson("/api/admin/schedules/{$schedule->id}");

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('schedules', ['id' => $schedule->id]);
    }

    /** @test */
    public function admin_can_toggle_schedule(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $schedule = Schedule::factory()->forDay(DayOfWeek::SUNDAY)->create([
            'is_active' => true,
        ]);

        $response = $this->putJson("/api/admin/schedules/{$schedule->id}/toggle");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'is_active' => false,
                ],
            ]);

        $this->assertFalse($schedule->fresh()->is_active);
    }

    /** @test */
    public function cannot_create_duplicate_day_schedule(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        Schedule::factory()->forDay(DayOfWeek::SUNDAY)->create();

        $response = $this->postJson('/api/admin/schedules', [
            'day_of_week' => DayOfWeek::SUNDAY->value,
            'start_time' => '09:00',
            'end_time' => '17:00',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['day_of_week']);
    }

    /** @test */
    public function end_time_must_be_after_start_time(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/admin/schedules', [
            'day_of_week' => DayOfWeek::SUNDAY->value,
            'start_time' => '17:00',
            'end_time' => '09:00',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_time']);
    }

    /** @test */
    public function break_end_required_when_break_start_provided(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/admin/schedules', [
            'day_of_week' => DayOfWeek::SUNDAY->value,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'break_start' => '13:00',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['break_end']);
    }

    /** @test */
    public function non_admin_cannot_access_schedules(): void
    {
        $patient = User::factory()->patient()->create();
        Sanctum::actingAs($patient);

        $response = $this->getJson('/api/admin/schedules');

        $response->assertForbidden();
    }

    /** @test */
    public function unauthenticated_user_cannot_access_schedules(): void
    {
        $response = $this->getJson('/api/admin/schedules');

        $response->assertUnauthorized();
    }
}
