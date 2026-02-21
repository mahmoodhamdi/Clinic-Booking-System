<?php

namespace Tests\Feature\Api;

use App\Enums\AppointmentStatus;
use App\Enums\DayOfWeek;
use App\Models\Appointment;
use App\Models\ClinicSetting;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\User;
use App\Models\Vacation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookingLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected User $patient;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        ClinicSetting::factory()->create([
            'slot_duration' => 30,
            'advance_booking_days' => 30,
            'max_patients_per_slot' => 1,
            'cancellation_hours' => 24,
        ]);

        $this->patient = User::factory()->patient()->create();
        $this->admin = User::factory()->admin()->create();
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
    public function complete_booking_lifecycle(): void
    {
        Sanctum::actingAs($this->patient);

        // Step 1: Patient books appointment
        $tomorrow = now()->addDay()->setTime(10, 0);
        $this->createScheduleForDate($tomorrow);

        $bookingResponse = $this->postJson('/api/appointments', [
            'datetime' => $tomorrow->toIso8601String(),
            'notes' => 'I have chest pain',
        ]);

        $bookingResponse->assertCreated()
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'pending',
                    'notes' => 'I have chest pain',
                ],
            ]);

        $appointmentId = $bookingResponse->json('data.id');
        $this->assertNotNull($appointmentId);

        // Step 2: Admin confirms appointment
        Sanctum::actingAs($this->admin);
        $confirmResponse = $this->postJson("/api/admin/appointments/{$appointmentId}/confirm");

        $confirmResponse->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'confirmed',
                ],
            ]);

        // Step 3: Verify appointment status changed
        $appointment = Appointment::find($appointmentId);
        $this->assertEquals(AppointmentStatus::CONFIRMED, $appointment->status);

        // Step 4: Admin creates medical record
        $medicalResponse = $this->postJson('/api/admin/medical-records', [
            'appointment_id' => $appointmentId,
            'diagnosis' => 'Hypertension',
            'symptoms' => 'Chest pain, dizziness',
            'examination_notes' => 'BP elevated',
            'treatment_plan' => 'Medication and rest',
            'vital_signs' => [
                'blood_pressure' => '150/95',
                'heart_rate' => 85,
                'temperature' => 36.8,
                'weight' => 75,
                'height' => 180,
            ],
        ]);

        $medicalResponse->assertCreated()
            ->assertJson([
                'success' => true,
                'data' => [
                    'diagnosis' => 'Hypertension',
                    'appointment_id' => $appointmentId,
                ],
            ]);

        $medicalRecordId = $medicalResponse->json('data.id');

        // Step 5: Admin creates prescription
        $prescriptionResponse = $this->postJson('/api/admin/prescriptions', [
            'medical_record_id' => $medicalRecordId,
            'instructions' => 'Take twice daily',
            'items' => [
                [
                    'medication_name' => 'Amlodipine',
                    'dosage' => '5mg',
                    'frequency' => 'Twice daily',
                    'duration' => '30 days',
                ],
            ],
        ]);

        $prescriptionResponse->assertCreated()
            ->assertJson([
                'success' => true,
                'data' => [
                    'medical_record_id' => $medicalRecordId,
                ],
            ]);

        // Step 6: Admin completes appointment
        $completeResponse = $this->postJson("/api/admin/appointments/{$appointmentId}/complete");

        $completeResponse->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'completed',
                ],
            ]);

        // Step 7: Admin records payment
        $paymentResponse = $this->postJson('/api/admin/payments', [
            'appointment_id' => $appointmentId,
            'amount' => 200,
            'method' => 'cash',
        ]);

        $paymentResponse->assertCreated()
            ->assertJson([
                'success' => true,
            ]);

        // Verify everything is in the database
        $this->assertDatabaseHas('appointments', [
            'id' => $appointmentId,
            'status' => AppointmentStatus::COMPLETED->value,
        ]);

        $this->assertDatabaseHas('medical_records', [
            'appointment_id' => $appointmentId,
        ]);

        $this->assertDatabaseHas('payments', [
            'appointment_id' => $appointmentId,
        ]);
    }

    /** @test */
    public function patient_can_book_cancel_and_rebook_same_slot(): void
    {
        Sanctum::actingAs($this->patient);

        $tomorrow = now()->addDay()->setTime(10, 0);
        $this->createScheduleForDate($tomorrow);

        // Book appointment
        $bookingResponse = $this->postJson('/api/appointments', [
            'datetime' => $tomorrow->toIso8601String(),
        ]);

        $appointmentId = $bookingResponse->json('data.id');

        // Cancel appointment
        $cancelResponse = $this->postJson("/api/appointments/{$appointmentId}/cancel", [
            'reason' => 'Personal reasons',
        ]);

        $cancelResponse->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'cancelled',
                ],
            ]);

        // Rebook the same slot
        $rebookResponse = $this->postJson('/api/appointments', [
            'datetime' => $tomorrow->toIso8601String(),
        ]);

        $rebookResponse->assertCreated()
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'pending',
                ],
            ]);

        // Verify it's a different appointment
        $newAppointmentId = $rebookResponse->json('data.id');
        $this->assertNotEquals($appointmentId, $newAppointmentId);
    }

    /** @test */
    public function patient_cannot_book_during_vacation_days(): void
    {
        Sanctum::actingAs($this->patient);

        // Create vacation for tomorrow
        $tomorrow = now()->addDay();
        Vacation::factory()->forDates(
            $tomorrow->toDateString(),
            $tomorrow->toDateString()
        )->create();

        $this->createScheduleForDate($tomorrow);

        $response = $this->postJson('/api/appointments', [
            'datetime' => $tomorrow->setTime(10, 0)->toIso8601String(),
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function patient_cannot_book_on_inactive_schedule_days(): void
    {
        Sanctum::actingAs($this->patient);

        $tomorrow = now()->addDay();
        $dayOfWeek = DayOfWeek::fromDate($tomorrow);

        // Create inactive schedule for that day
        Schedule::factory()->forDay($dayOfWeek)->inactive()->create();

        $response = $this->postJson('/api/appointments', [
            'datetime' => $tomorrow->setTime(10, 0)->toIso8601String(),
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function double_booking_respects_max_patients_per_slot(): void
    {
        $slot = now()->addDay()->setTime(10, 0);
        $this->createScheduleForDate($slot);

        // Update clinic setting to allow 1 patient per slot
        ClinicSetting::first()->update(['max_patients_per_slot' => 1]);

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

        $response2->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function patient_cannot_book_in_past(): void
    {
        Sanctum::actingAs($this->patient);

        $yesterday = now()->subDay();

        $response = $this->postJson('/api/appointments', [
            'datetime' => $yesterday->toIso8601String(),
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function patient_cannot_book_beyond_advance_booking_days(): void
    {
        Sanctum::actingAs($this->patient);

        ClinicSetting::first()->update(['advance_booking_days' => 7]);

        $tooFarFuture = now()->addDays(10)->setTime(10, 0);
        $this->createScheduleForDate($tooFarFuture);

        $response = $this->postJson('/api/appointments', [
            'datetime' => $tooFarFuture->toIso8601String(),
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function appointment_cancellation_changes_status_to_cancelled(): void
    {
        Sanctum::actingAs($this->patient);

        // Create appointment for tomorrow + 2 days (to avoid cancellation window)
        $appointmentTime = now()->addDays(3)->setTime(10, 0)->setMinutes(0)->setSeconds(0);
        $this->createScheduleForDate($appointmentTime);

        $bookResponse = $this->postJson('/api/appointments', [
            'datetime' => $appointmentTime->toIso8601String(),
        ]);

        $appointmentId = $bookResponse->json('data.id');

        // Cancel appointment
        $cancelResponse = $this->postJson("/api/appointments/{$appointmentId}/cancel", [
            'reason' => 'Changed my mind',
        ]);

        // Should succeed
        $cancelResponse->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'cancelled',
                ],
            ]);

        // Verify appointment status changed
        $this->assertDatabaseHas('appointments', [
            'id' => $appointmentId,
            'status' => AppointmentStatus::CANCELLED->value,
        ]);
    }
}
