<?php

namespace Tests\Unit\Services;

use App\Enums\AppointmentStatus;
use App\Enums\CancelledBy;
use App\Enums\DayOfWeek;
use App\Exceptions\BusinessLogicException;
use App\Exceptions\SlotNotAvailableException;
use App\Models\Appointment;
use App\Models\ClinicSetting;
use App\Models\Schedule;
use App\Models\User;
use App\Services\AppointmentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AppointmentService $service;

    protected function setUp(): void
    {
        parent::setUp();

        ClinicSetting::factory()->create([
            'slot_duration' => 30,
            'advance_booking_days' => 30,
        ]);

        $this->service = app(AppointmentService::class);
    }

    protected function createScheduleForDate(Carbon $date): void
    {
        $dayOfWeek = DayOfWeek::fromDate($date);
        Schedule::factory()->forDay($dayOfWeek)->create([
            'start_time' => '09:00',
            'end_time' => '17:00',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function can_book_appointment(): void
    {
        $patient = User::factory()->patient()->create();
        $tomorrow = now()->addDay()->setTime(10, 0);
        $this->createScheduleForDate($tomorrow);

        $appointment = $this->service->book($patient, $tomorrow, 'ملاحظات');

        $this->assertInstanceOf(Appointment::class, $appointment);
        $this->assertEquals($patient->id, $appointment->user_id);
        $this->assertEquals(AppointmentStatus::PENDING, $appointment->status);
        $this->assertEquals('ملاحظات', $appointment->notes);
    }

    /** @test */
    public function cannot_book_already_booked_slot(): void
    {
        $patient1 = User::factory()->patient()->create();
        $patient2 = User::factory()->patient()->create();
        $tomorrow = now()->addDay()->setTime(10, 0);
        $this->createScheduleForDate($tomorrow);

        // First booking
        $this->service->book($patient1, $tomorrow);

        // Second booking should fail
        $this->expectException(SlotNotAvailableException::class);
        $this->service->book($patient2, $tomorrow);
    }

    /** @test */
    public function cannot_book_with_too_many_no_shows(): void
    {
        $patient = User::factory()->patient()->create();
        $tomorrow = now()->addDay()->setTime(10, 0);
        $this->createScheduleForDate($tomorrow);

        // Create 3 no-shows
        Appointment::factory()
            ->forPatient($patient)
            ->noShow()
            ->count(3)
            ->create([
                'appointment_date' => now()->subDays(5)->toDateString(),
            ]);

        $this->expectException(BusinessLogicException::class);
        $this->service->book($patient, $tomorrow);
    }

    /** @test */
    public function can_check_if_booking_is_possible(): void
    {
        $patient = User::factory()->patient()->create();
        $tomorrow = now()->addDay()->setTime(10, 0);
        $this->createScheduleForDate($tomorrow);

        $result = $this->service->canBook($patient, $tomorrow);

        $this->assertTrue($result['can_book']);
        $this->assertNull($result['reason']);
    }

    /** @test */
    public function can_confirm_pending_appointment(): void
    {
        $appointment = Appointment::factory()->pending()->create();

        $result = $this->service->confirm($appointment);

        $this->assertEquals(AppointmentStatus::CONFIRMED, $result->status);
        $this->assertNotNull($result->confirmed_at);
    }

    /** @test */
    public function cannot_confirm_non_pending_appointment(): void
    {
        $appointment = Appointment::factory()->confirmed()->create();

        $this->expectException(BusinessLogicException::class);
        $this->service->confirm($appointment);
    }

    /** @test */
    public function can_complete_confirmed_appointment(): void
    {
        $appointment = Appointment::factory()->confirmed()->create();

        $result = $this->service->complete($appointment, 'تم الكشف بنجاح');

        $this->assertEquals(AppointmentStatus::COMPLETED, $result->status);
        $this->assertEquals('تم الكشف بنجاح', $result->admin_notes);
        $this->assertNotNull($result->completed_at);
    }

    /** @test */
    public function cannot_complete_pending_appointment(): void
    {
        $appointment = Appointment::factory()->pending()->create();

        $this->expectException(BusinessLogicException::class);
        $this->service->complete($appointment);
    }

    /** @test */
    public function can_cancel_active_appointment(): void
    {
        $appointment = Appointment::factory()->pending()->create();

        $result = $this->service->cancel($appointment, 'ظروف طارئة', CancelledBy::PATIENT);

        $this->assertEquals(AppointmentStatus::CANCELLED, $result->status);
        $this->assertEquals('ظروف طارئة', $result->cancellation_reason);
        $this->assertEquals(CancelledBy::PATIENT, $result->cancelled_by);
    }

    /** @test */
    public function cannot_cancel_completed_appointment(): void
    {
        $appointment = Appointment::factory()->completed()->create();

        $this->expectException(BusinessLogicException::class);
        $this->service->cancel($appointment, 'سبب', CancelledBy::ADMIN);
    }

    /** @test */
    public function can_mark_as_no_show(): void
    {
        $appointment = Appointment::factory()->confirmed()->create();

        $result = $this->service->markNoShow($appointment);

        $this->assertEquals(AppointmentStatus::NO_SHOW, $result->status);
    }

    /** @test */
    public function cannot_mark_pending_as_no_show(): void
    {
        $appointment = Appointment::factory()->pending()->create();

        $this->expectException(BusinessLogicException::class);
        $this->service->markNoShow($appointment);
    }

    /** @test */
    public function can_get_patient_appointments(): void
    {
        $patient = User::factory()->patient()->create();
        Appointment::factory()->forPatient($patient)->count(3)->create();
        Appointment::factory()->create(); // Another patient

        $appointments = $this->service->getPatientAppointments($patient);

        $this->assertCount(3, $appointments);
    }

    /** @test */
    public function can_filter_patient_appointments_by_status(): void
    {
        $patient = User::factory()->patient()->create();
        Appointment::factory()->forPatient($patient)->pending()->count(2)->create();
        Appointment::factory()->forPatient($patient)->completed()->create();

        $appointments = $this->service->getPatientAppointments($patient, 'pending');

        $this->assertCount(2, $appointments);
    }

    /** @test */
    public function can_get_today_appointments(): void
    {
        Appointment::factory()->today()->count(2)->create();
        Appointment::factory()->tomorrow()->create();

        $appointments = $this->service->getTodayAppointments();

        $this->assertCount(2, $appointments);
    }

    /** @test */
    public function can_get_appointments_for_date(): void
    {
        $date = now()->addDays(3);
        Appointment::factory()->forDate($date->toDateString())->count(3)->create();
        Appointment::factory()->tomorrow()->create();

        $appointments = $this->service->getAppointmentsForDate($date);

        $this->assertCount(3, $appointments);
    }

    /** @test */
    public function can_get_statistics(): void
    {
        Appointment::factory()->pending()->count(2)->create();
        Appointment::factory()->confirmed()->create();
        Appointment::factory()->completed()->count(3)->create();
        Appointment::factory()->cancelled()->create();

        $stats = $this->service->getStatistics();

        $this->assertEquals(7, $stats['total']);
        $this->assertEquals(2, $stats['by_status']['pending']);
        $this->assertEquals(1, $stats['by_status']['confirmed']);
        $this->assertEquals(3, $stats['by_status']['completed']);
        $this->assertEquals(1, $stats['by_status']['cancelled']);
    }

    /** @test */
    public function can_check_if_patient_can_cancel(): void
    {
        $patient = User::factory()->patient()->create();
        $appointment = Appointment::factory()
            ->forPatient($patient)
            ->pending()
            ->forDate(now()->addDay()->toDateString())
            ->create();

        $result = $this->service->canCancel($appointment, $patient);

        $this->assertTrue($result['can_cancel']);
    }

    /** @test */
    public function patient_cannot_cancel_others_appointment(): void
    {
        $patient1 = User::factory()->patient()->create();
        $patient2 = User::factory()->patient()->create();
        $appointment = Appointment::factory()
            ->forPatient($patient1)
            ->pending()
            ->forDate(now()->addDay()->toDateString())
            ->create();

        $result = $this->service->canCancel($appointment, $patient2);

        $this->assertFalse($result['can_cancel']);
    }
}
