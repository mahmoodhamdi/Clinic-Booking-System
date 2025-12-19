<?php

namespace Tests\Feature\Api;

use App\Enums\AppointmentStatus;
use App\Enums\DayOfWeek;
use App\Models\Appointment;
use App\Models\ClinicSetting;
use App\Models\Schedule;
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

        ClinicSetting::factory()->create([
            'slot_duration' => 30,
            'advance_booking_days' => 30,
        ]);
    }

    protected function createScheduleForDate($date): void
    {
        $dayOfWeek = DayOfWeek::fromDate($date);
        Schedule::factory()->forDay($dayOfWeek)->create([
            'start_time' => '09:00',
            'end_time' => '17:00',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function patient_can_book_appointment(): void
    {
        $patient = User::factory()->patient()->create();
        Sanctum::actingAs($patient);

        $tomorrow = now()->addDay()->setTime(10, 0);
        $this->createScheduleForDate($tomorrow);

        $response = $this->postJson('/api/appointments', [
            'datetime' => $tomorrow->toIso8601String(),
            'notes' => 'ملاحظات المريض',
        ]);

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'pending',
                    'notes' => 'ملاحظات المريض',
                ],
            ]);

        $this->assertDatabaseHas('appointments', [
            'user_id' => $patient->id,
            'notes' => 'ملاحظات المريض',
        ]);
    }

    /** @test */
    public function patient_cannot_book_in_past(): void
    {
        $patient = User::factory()->patient()->create();
        Sanctum::actingAs($patient);

        $yesterday = now()->subDay();

        $response = $this->postJson('/api/appointments', [
            'datetime' => $yesterday->toIso8601String(),
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function patient_cannot_double_book_same_slot(): void
    {
        $patient = User::factory()->patient()->create();
        Sanctum::actingAs($patient);

        $tomorrow = now()->addDay()->setTime(10, 0);
        $this->createScheduleForDate($tomorrow);

        // First booking
        $this->postJson('/api/appointments', [
            'datetime' => $tomorrow->toIso8601String(),
        ])->assertCreated();

        // Second booking same slot
        $response = $this->postJson('/api/appointments', [
            'datetime' => $tomorrow->toIso8601String(),
        ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    /** @test */
    public function patient_can_list_their_appointments(): void
    {
        $patient = User::factory()->patient()->create();
        Sanctum::actingAs($patient);

        Appointment::factory()->forPatient($patient)->count(3)->create();
        Appointment::factory()->create(); // Other patient

        $response = $this->getJson('/api/appointments');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function patient_can_filter_appointments_by_status(): void
    {
        $patient = User::factory()->patient()->create();
        Sanctum::actingAs($patient);

        Appointment::factory()->forPatient($patient)->pending()->count(2)->create();
        Appointment::factory()->forPatient($patient)->completed()->create();

        $response = $this->getJson('/api/appointments?status=pending');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function patient_can_get_upcoming_appointments(): void
    {
        $patient = User::factory()->patient()->create();
        Sanctum::actingAs($patient);

        Appointment::factory()
            ->forPatient($patient)
            ->pending()
            ->forDate(now()->addDay()->toDateString())
            ->count(2)
            ->create();

        Appointment::factory()
            ->forPatient($patient)
            ->completed()
            ->past()
            ->create();

        $response = $this->getJson('/api/appointments/upcoming');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function patient_can_view_their_appointment(): void
    {
        $patient = User::factory()->patient()->create();
        Sanctum::actingAs($patient);

        $appointment = Appointment::factory()->forPatient($patient)->create();

        $response = $this->getJson("/api/appointments/{$appointment->id}");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $appointment->id,
                ],
            ]);
    }

    /** @test */
    public function patient_cannot_view_others_appointment(): void
    {
        $patient = User::factory()->patient()->create();
        Sanctum::actingAs($patient);

        $appointment = Appointment::factory()->create(); // Other patient

        $response = $this->getJson("/api/appointments/{$appointment->id}");

        $response->assertForbidden();
    }

    /** @test */
    public function patient_can_cancel_their_appointment(): void
    {
        $patient = User::factory()->patient()->create();
        Sanctum::actingAs($patient);

        $appointment = Appointment::factory()
            ->forPatient($patient)
            ->pending()
            ->forDate(now()->addDay()->toDateString())
            ->create();

        $response = $this->postJson("/api/appointments/{$appointment->id}/cancel", [
            'reason' => 'ظروف طارئة',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'cancelled',
                    'cancellation_reason' => 'ظروف طارئة',
                ],
            ]);
    }

    /** @test */
    public function patient_cannot_cancel_others_appointment(): void
    {
        $patient = User::factory()->patient()->create();
        Sanctum::actingAs($patient);

        $appointment = Appointment::factory()
            ->pending()
            ->forDate(now()->addDay()->toDateString())
            ->create();

        $response = $this->postJson("/api/appointments/{$appointment->id}/cancel", [
            'reason' => 'سبب',
        ]);

        $response->assertForbidden();
    }

    /** @test */
    public function patient_cannot_cancel_completed_appointment(): void
    {
        $patient = User::factory()->patient()->create();
        Sanctum::actingAs($patient);

        $appointment = Appointment::factory()
            ->forPatient($patient)
            ->completed()
            ->create();

        $response = $this->postJson("/api/appointments/{$appointment->id}/cancel", [
            'reason' => 'سبب',
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function patient_can_check_if_can_book(): void
    {
        $patient = User::factory()->patient()->create();
        Sanctum::actingAs($patient);

        $tomorrow = now()->addDay()->setTime(10, 0);
        $this->createScheduleForDate($tomorrow);

        $response = $this->postJson('/api/appointments/check', [
            'datetime' => $tomorrow->toIso8601String(),
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'can_book' => true,
                ],
            ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_book(): void
    {
        $response = $this->postJson('/api/appointments', [
            'datetime' => now()->addDay()->toIso8601String(),
        ]);

        $response->assertUnauthorized();
    }

    /** @test */
    public function cancel_reason_is_required(): void
    {
        $patient = User::factory()->patient()->create();
        Sanctum::actingAs($patient);

        $appointment = Appointment::factory()
            ->forPatient($patient)
            ->pending()
            ->forDate(now()->addDay()->toDateString())
            ->create();

        $response = $this->postJson("/api/appointments/{$appointment->id}/cancel", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }

    /** @test */
    public function patient_with_many_no_shows_cannot_book(): void
    {
        $patient = User::factory()->patient()->create();
        Sanctum::actingAs($patient);

        // Create 3 no-shows
        Appointment::factory()
            ->forPatient($patient)
            ->noShow()
            ->count(3)
            ->create([
                'appointment_date' => now()->subDays(5)->toDateString(),
            ]);

        $tomorrow = now()->addDay()->setTime(10, 0);
        $this->createScheduleForDate($tomorrow);

        $response = $this->postJson('/api/appointments', [
            'datetime' => $tomorrow->toIso8601String(),
        ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }
}
