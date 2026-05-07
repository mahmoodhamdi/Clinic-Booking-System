<?php

namespace Tests\Feature\Api\Appointments;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\ClinicSetting;
use App\Models\User;
use App\Notifications\AppointmentConfirmed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AppointmentEmailNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        ClinicSetting::create([
            'clinic_name' => 'Test Clinic',
            'doctor_name' => 'Test Doctor',
            'phone' => '+201012345678',
            'email' => 'clinic@example.com',
            'slot_duration' => 30,
            'max_patients_per_slot' => 1,
            'advance_booking_days' => 30,
            'cancellation_hours' => 24,
        ]);

        config()->set('mail.from.address', 'noreply@example.com');
    }

    /** @test */
    public function database_notification_is_always_sent_on_confirm(): void
    {
        Notification::fake();
        config()->set('clinic.notifications.email_enabled', false);

        $patient = User::factory()->create(['email' => 'patient@example.com']);
        $appointment = Appointment::factory()->create([
            'user_id' => $patient->id,
            'status' => AppointmentStatus::PENDING,
        ]);

        $appointment->update(['status' => AppointmentStatus::CONFIRMED]);

        Notification::assertSentTo($patient, AppointmentConfirmed::class, function ($notification, $channels) {
            return in_array('database', $channels) && ! in_array('mail', $channels);
        });
    }

    /** @test */
    public function email_channel_is_added_when_enabled_and_user_has_email(): void
    {
        Notification::fake();
        config()->set('clinic.notifications.email_enabled', true);

        $patient = User::factory()->create(['email' => 'patient@example.com']);
        $appointment = Appointment::factory()->create([
            'user_id' => $patient->id,
            'status' => AppointmentStatus::PENDING,
        ]);

        $appointment->update(['status' => AppointmentStatus::CONFIRMED]);

        Notification::assertSentTo($patient, AppointmentConfirmed::class, function ($notification, $channels) {
            return in_array('database', $channels) && in_array('mail', $channels);
        });
    }

    /** @test */
    public function email_channel_is_skipped_when_user_has_no_email(): void
    {
        Notification::fake();
        config()->set('clinic.notifications.email_enabled', true);

        $patient = User::factory()->create(['email' => null]);
        $appointment = Appointment::factory()->create([
            'user_id' => $patient->id,
            'status' => AppointmentStatus::PENDING,
        ]);

        $appointment->update(['status' => AppointmentStatus::CONFIRMED]);

        Notification::assertSentTo($patient, AppointmentConfirmed::class, function ($notification, $channels) {
            return ! in_array('mail', $channels);
        });
    }

    /** @test */
    public function email_channel_is_skipped_when_mail_from_is_empty(): void
    {
        Notification::fake();
        config()->set('clinic.notifications.email_enabled', true);
        config()->set('mail.from.address', '');

        $patient = User::factory()->create(['email' => 'patient@example.com']);
        $appointment = Appointment::factory()->create([
            'user_id' => $patient->id,
            'status' => AppointmentStatus::PENDING,
        ]);

        $appointment->update(['status' => AppointmentStatus::CONFIRMED]);

        Notification::assertSentTo($patient, AppointmentConfirmed::class, function ($notification, $channels) {
            return ! in_array('mail', $channels);
        });
    }

    /** @test */
    public function notification_does_not_fire_when_status_is_unchanged(): void
    {
        Notification::fake();

        $patient = User::factory()->create();
        $appointment = Appointment::factory()->create([
            'user_id' => $patient->id,
            'status' => AppointmentStatus::CONFIRMED,
        ]);

        // Touch a non-status field — should not re-trigger the confirmation.
        $appointment->update(['notes' => 'updated note']);

        Notification::assertNothingSentTo($patient);
    }
}
