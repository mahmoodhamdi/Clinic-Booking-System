<?php

namespace Tests\Feature\Console;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\ClinicSetting;
use App\Models\User;
use App\Notifications\AppointmentReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendAppointmentRemindersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        ClinicSetting::create([
            'clinic_name' => 'Test',
            'doctor_name' => 'Dr',
            'phone' => '+201012345678',
            'slot_duration' => 30,
            'max_patients_per_slot' => 1,
            'advance_booking_days' => 30,
            'cancellation_hours' => 24,
        ]);
    }

    private function freezeAt(string $iso): void
    {
        Carbon::setTestNow(Carbon::parse($iso));
    }

    /** @test */
    public function sends_reminder_for_confirmed_appointment_24h_out(): void
    {
        Notification::fake();
        $this->freezeAt('2026-05-07 10:00:00');

        $patient = User::factory()->create(['phone' => '01200000001']);
        $appointment = Appointment::factory()->create([
            'user_id' => $patient->id,
            'status' => AppointmentStatus::CONFIRMED,
            'appointment_date' => '2026-05-08',
            'appointment_time' => '10:00',
        ]);

        // Reproduce the command's candidate query inline so a failure here
        // tells us *which* condition excludes the appointment instead of
        // just "no notification sent".
        $stored = Appointment::find($appointment->id);
        $this->assertNotNull($stored, 'appointment was not persisted');
        $this->assertSame(
            '2026-05-08',
            $stored->appointment_date->format('Y-m-d'),
            'appointment_date round-trip mismatch'
        );
        $this->assertSame(AppointmentStatus::CONFIRMED, $stored->status, 'status round-trip mismatch');
        $this->assertNull($stored->reminder_sent_at, 'reminder_sent_at should be null');

        $windowStart = now()->addHours(23);
        $windowEnd = now()->addHours(25);
        $datesFilter = array_unique([$windowStart->toDateString(), $windowEnd->toDateString()]);
        $this->assertContains('2026-05-08', $datesFilter, 'window did not include 2026-05-08');

        $candidates = Appointment::query()
            ->where('status', AppointmentStatus::CONFIRMED)
            ->whereNull('reminder_sent_at')
            ->whereIn('appointment_date', $datesFilter)
            ->get();

        $this->assertCount(
            1,
            $candidates,
            sprintf(
                'candidate query returned %d rows; expected 1. dates=%s, all rows=%s',
                $candidates->count(),
                json_encode($datesFilter),
                Appointment::all()->toJson()
            )
        );

        $this->artisan('appointments:send-reminders --hours=24')->assertSuccessful();

        Notification::assertSentTo($patient, AppointmentReminder::class);
        $this->assertNotNull($appointment->fresh()->reminder_sent_at);
    }

    /** @test */
    public function ignores_appointments_outside_the_window(): void
    {
        Notification::fake();
        $this->freezeAt('2026-05-07 10:00:00');

        $patient = User::factory()->create();
        // 48h out — far outside the 23-25h window
        Appointment::factory()->create([
            'user_id' => $patient->id,
            'status' => AppointmentStatus::CONFIRMED,
            'appointment_date' => '2026-05-09',
            'appointment_time' => '10:00',
        ]);

        $this->artisan('appointments:send-reminders --hours=24')->assertSuccessful();

        Notification::assertNothingSentTo($patient);
    }

    /** @test */
    public function skips_appointments_already_reminded(): void
    {
        Notification::fake();
        $this->freezeAt('2026-05-07 10:00:00');

        $patient = User::factory()->create();
        Appointment::factory()->create([
            'user_id' => $patient->id,
            'status' => AppointmentStatus::CONFIRMED,
            'appointment_date' => '2026-05-08',
            'appointment_time' => '10:00',
            'reminder_sent_at' => now()->subHours(2),
        ]);

        $this->artisan('appointments:send-reminders --hours=24')->assertSuccessful();

        Notification::assertNothingSentTo($patient);
    }

    /** @test */
    public function skips_pending_or_cancelled_appointments(): void
    {
        Notification::fake();
        $this->freezeAt('2026-05-07 10:00:00');

        $patient = User::factory()->create();
        foreach ([AppointmentStatus::PENDING, AppointmentStatus::CANCELLED, AppointmentStatus::COMPLETED] as $status) {
            Appointment::factory()->create([
                'user_id' => $patient->id,
                'status' => $status,
                'appointment_date' => '2026-05-08',
                'appointment_time' => '10:00',
            ]);
        }

        $this->artisan('appointments:send-reminders --hours=24')->assertSuccessful();

        Notification::assertNothingSentTo($patient);
    }

    /** @test */
    public function dry_run_does_not_send_or_mark_reminded(): void
    {
        Notification::fake();
        $this->freezeAt('2026-05-07 10:00:00');

        $patient = User::factory()->create();
        $appointment = Appointment::factory()->create([
            'user_id' => $patient->id,
            'status' => AppointmentStatus::CONFIRMED,
            'appointment_date' => '2026-05-08',
            'appointment_time' => '10:00',
        ]);

        $this->artisan('appointments:send-reminders --hours=24 --dry-run')->assertSuccessful();

        Notification::assertNothingSentTo($patient);
        $this->assertNull($appointment->fresh()->reminder_sent_at);
    }
}
