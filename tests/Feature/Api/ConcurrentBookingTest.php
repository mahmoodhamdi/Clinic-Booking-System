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

class ConcurrentBookingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        ClinicSetting::factory()->create([
            'slot_duration' => 30,
            'advance_booking_days' => 30,
            'max_patients_per_slot' => 1,
            'cancellation_hours' => 24,
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
    public function second_patient_cannot_book_slot_already_taken(): void
    {
        $slot = now()->addDay()->setTime(10, 0);
        $this->createScheduleForDate($slot);

        // First patient books
        $patient1 = User::factory()->patient()->create();
        Sanctum::actingAs($patient1);
        $response1 = $this->postJson('/api/appointments', [
            'datetime' => $slot->toIso8601String(),
        ]);

        $response1->assertCreated();

        // Second patient tries to book same slot
        $patient2 = User::factory()->patient()->create();
        Sanctum::actingAs($patient2);
        $response2 = $this->postJson('/api/appointments', [
            'datetime' => $slot->toIso8601String(),
        ]);

        // Should fail because slot is taken
        $response2->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function rapid_status_changes_on_same_appointment_are_handled_correctly(): void
    {
        $admin = User::factory()->admin()->create();
        $patient = User::factory()->patient()->create();

        // Create appointment
        $tomorrow = now()->addDay()->setTime(10, 0);
        $this->createScheduleForDate($tomorrow);

        Sanctum::actingAs($patient);
        $bookResponse = $this->postJson('/api/appointments', [
            'datetime' => $tomorrow->toIso8601String(),
        ]);

        $appointmentId = $bookResponse->json('data.id');

        Sanctum::actingAs($admin);

        // Rapid status changes
        $response1 = $this->postJson("/api/admin/appointments/{$appointmentId}/confirm");
        $response1->assertOk();

        $response2 = $this->postJson("/api/admin/appointments/{$appointmentId}/complete");
        $response2->assertOk();

        // Verify final status is completed
        $appointment = Appointment::find($appointmentId);
        $this->assertEquals(AppointmentStatus::COMPLETED, $appointment->status);
        $this->assertNotNull($appointment->completed_at);
    }

    /** @test */
    public function creating_payment_after_completing_appointment_works(): void
    {
        $admin = User::factory()->admin()->create();
        $patient = User::factory()->patient()->create();

        // Create appointment
        $tomorrow = now()->addDay()->setTime(10, 0);
        $this->createScheduleForDate($tomorrow);

        Sanctum::actingAs($patient);
        $bookResponse = $this->postJson('/api/appointments', [
            'datetime' => $tomorrow->toIso8601String(),
        ]);

        $appointmentId = $bookResponse->json('data.id');

        Sanctum::actingAs($admin);

        // Confirm and complete appointment first
        $this->postJson("/api/admin/appointments/{$appointmentId}/confirm");
        $this->postJson("/api/admin/appointments/{$appointmentId}/complete");

        // Create payment
        $paymentResponse = $this->postJson('/api/admin/payments', [
            'appointment_id' => $appointmentId,
            'amount' => 200,
            'method' => 'cash',
        ]);

        $paymentResponse->assertCreated()
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function cannot_cancel_already_completed_appointment(): void
    {
        $admin = User::factory()->admin()->create();
        $patient = User::factory()->patient()->create();

        // Create and complete appointment
        $tomorrow = now()->addDay()->setTime(10, 0);
        $this->createScheduleForDate($tomorrow);

        Sanctum::actingAs($patient);
        $bookResponse = $this->postJson('/api/appointments', [
            'datetime' => $tomorrow->toIso8601String(),
        ]);

        $appointmentId = $bookResponse->json('data.id');

        Sanctum::actingAs($admin);

        // Complete appointment
        $this->postJson("/api/admin/appointments/{$appointmentId}/complete");

        // Try to cancel (should fail since already completed)
        $cancelResponse = $this->postJson("/api/admin/appointments/{$appointmentId}/cancel");

        $cancelResponse->assertStatus(422);
    }
}
