<?php

namespace Tests\Unit\Models;

use App\Enums\AppointmentStatus;
use App\Enums\CancelledBy;
use App\Models\Appointment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function belongs_to_patient(): void
    {
        $patient = User::factory()->patient()->create();
        $appointment = Appointment::factory()->forPatient($patient)->create();

        $this->assertInstanceOf(User::class, $appointment->patient);
        $this->assertEquals($patient->id, $appointment->patient->id);
    }

    /** @test */
    public function has_pending_scope(): void
    {
        Appointment::factory()->pending()->count(2)->create();
        Appointment::factory()->confirmed()->create();

        $this->assertCount(2, Appointment::pending()->get());
    }

    /** @test */
    public function has_confirmed_scope(): void
    {
        Appointment::factory()->pending()->create();
        Appointment::factory()->confirmed()->count(3)->create();

        $this->assertCount(3, Appointment::confirmed()->get());
    }

    /** @test */
    public function has_completed_scope(): void
    {
        Appointment::factory()->pending()->create();
        Appointment::factory()->completed()->count(2)->create();

        $this->assertCount(2, Appointment::completed()->get());
    }

    /** @test */
    public function has_cancelled_scope(): void
    {
        Appointment::factory()->pending()->create();
        Appointment::factory()->cancelled()->count(2)->create();

        $this->assertCount(2, Appointment::cancelled()->get());
    }

    /** @test */
    public function has_no_show_scope(): void
    {
        Appointment::factory()->pending()->create();
        Appointment::factory()->noShow()->count(2)->create();

        $this->assertCount(2, Appointment::noShow()->get());
    }

    /** @test */
    public function has_active_scope(): void
    {
        Appointment::factory()->pending()->create();
        Appointment::factory()->confirmed()->create();
        Appointment::factory()->completed()->create();
        Appointment::factory()->cancelled()->create();

        $this->assertCount(2, Appointment::active()->get());
    }

    /** @test */
    public function has_for_date_scope(): void
    {
        $tomorrow = now()->addDay()->toDateString();
        Appointment::factory()->forDate($tomorrow)->count(2)->create();
        Appointment::factory()->forDate(now()->addDays(3)->toDateString())->create();

        $this->assertCount(2, Appointment::forDate($tomorrow)->get());
    }

    /** @test */
    public function has_for_patient_scope(): void
    {
        $patient = User::factory()->patient()->create();
        Appointment::factory()->forPatient($patient)->count(3)->create();
        Appointment::factory()->create();

        $this->assertCount(3, Appointment::forPatient($patient->id)->get());
    }

    /** @test */
    public function has_today_scope(): void
    {
        Appointment::factory()->today()->count(2)->create();
        Appointment::factory()->tomorrow()->create();

        $this->assertCount(2, Appointment::today()->get());
    }

    /** @test */
    public function has_formatted_date_accessor(): void
    {
        $date = now()->addDay()->toDateString();
        $appointment = Appointment::factory()->forDate($date)->create();

        $this->assertEquals($date, $appointment->formatted_date);
    }

    /** @test */
    public function has_formatted_time_accessor(): void
    {
        $appointment = Appointment::factory()->forTime('10:30')->create();

        $this->assertEquals('10:30', $appointment->formatted_time);
    }

    /** @test */
    public function has_datetime_accessor(): void
    {
        $date = now()->addDay()->toDateString();
        $time = '14:00';
        $appointment = Appointment::factory()->forDateTime($date, $time)->create();

        $this->assertInstanceOf(Carbon::class, $appointment->datetime);
        $this->assertEquals($date, $appointment->datetime->toDateString());
        $this->assertEquals($time, $appointment->datetime->format('H:i'));
    }

    /** @test */
    public function has_day_name_accessor(): void
    {
        $appointment = Appointment::factory()->create();

        $this->assertNotEmpty($appointment->day_name);
    }

    /** @test */
    public function has_status_label_accessor(): void
    {
        $appointment = Appointment::factory()->pending()->create();

        $this->assertEquals('في الانتظار', $appointment->status_label);
    }

    /** @test */
    public function can_check_if_pending(): void
    {
        $pending = Appointment::factory()->pending()->create();
        $confirmed = Appointment::factory()->confirmed()->create();

        $this->assertTrue($pending->isPending());
        $this->assertFalse($confirmed->isPending());
    }

    /** @test */
    public function can_check_if_confirmed(): void
    {
        $pending = Appointment::factory()->pending()->create();
        $confirmed = Appointment::factory()->confirmed()->create();

        $this->assertFalse($pending->isConfirmed());
        $this->assertTrue($confirmed->isConfirmed());
    }

    /** @test */
    public function can_check_if_active(): void
    {
        $pending = Appointment::factory()->pending()->create();
        $completed = Appointment::factory()->completed()->create();

        $this->assertTrue($pending->isActive());
        $this->assertFalse($completed->isActive());
    }

    /** @test */
    public function can_confirm_appointment(): void
    {
        $appointment = Appointment::factory()->pending()->create();

        $appointment->confirm();

        $this->assertEquals(AppointmentStatus::CONFIRMED, $appointment->fresh()->status);
        $this->assertNotNull($appointment->fresh()->confirmed_at);
    }

    /** @test */
    public function can_complete_appointment(): void
    {
        $appointment = Appointment::factory()->confirmed()->create();

        $appointment->complete('ملاحظات الطبيب');

        $appointment->refresh();
        $this->assertEquals(AppointmentStatus::COMPLETED, $appointment->status);
        $this->assertNotNull($appointment->completed_at);
        $this->assertEquals('ملاحظات الطبيب', $appointment->admin_notes);
    }

    /** @test */
    public function can_cancel_appointment(): void
    {
        $appointment = Appointment::factory()->pending()->create();

        $appointment->cancel('سبب الإلغاء', CancelledBy::PATIENT);

        $appointment->refresh();
        $this->assertEquals(AppointmentStatus::CANCELLED, $appointment->status);
        $this->assertEquals('سبب الإلغاء', $appointment->cancellation_reason);
        $this->assertEquals(CancelledBy::PATIENT, $appointment->cancelled_by);
        $this->assertNotNull($appointment->cancelled_at);
    }

    /** @test */
    public function can_mark_as_no_show(): void
    {
        $appointment = Appointment::factory()->confirmed()->create();

        $appointment->markNoShow();

        $this->assertEquals(AppointmentStatus::NO_SHOW, $appointment->fresh()->status);
    }

    /** @test */
    public function can_check_if_slot_is_booked(): void
    {
        $date = now()->addDay();
        $time = '10:00';

        Appointment::factory()
            ->forDateTime($date->toDateString(), $time)
            ->pending()
            ->create();

        $this->assertTrue(Appointment::isSlotBooked($date, $time));
        $this->assertFalse(Appointment::isSlotBooked($date, '11:00'));
    }

    /** @test */
    public function cancelled_slot_is_not_considered_booked(): void
    {
        $date = now()->addDay();
        $time = '10:00';

        Appointment::factory()
            ->forDateTime($date->toDateString(), $time)
            ->cancelled()
            ->create();

        $this->assertFalse(Appointment::isSlotBooked($date, $time));
    }

    /** @test */
    public function can_get_no_show_count_for_patient(): void
    {
        $patient = User::factory()->patient()->create();

        Appointment::factory()
            ->forPatient($patient)
            ->noShow()
            ->count(2)
            ->create([
                'appointment_date' => now()->subDays(5)->toDateString(),
            ]);

        // Old no-show (over 30 days)
        Appointment::factory()
            ->forPatient($patient)
            ->noShow()
            ->create([
                'appointment_date' => now()->subDays(35)->toDateString(),
            ]);

        $this->assertEquals(2, Appointment::getNoShowCountForPatient($patient->id));
    }

    /** @test */
    public function can_cancel_accessor_returns_true_for_active_future_appointment(): void
    {
        $appointment = Appointment::factory()
            ->pending()
            ->forDate(now()->addDay()->toDateString())
            ->create();

        $this->assertTrue($appointment->can_cancel);
    }

    /** @test */
    public function can_cancel_accessor_returns_false_for_completed_appointment(): void
    {
        $appointment = Appointment::factory()
            ->completed()
            ->forDate(now()->addDay()->toDateString())
            ->create();

        $this->assertFalse($appointment->can_cancel);
    }

    // ==================== Soft Delete Tests ====================

    /** @test */
    public function appointment_can_be_soft_deleted(): void
    {
        $appointment = Appointment::factory()->create();
        $appointment->delete();

        $this->assertSoftDeleted('appointments', ['id' => $appointment->id]);
        $this->assertNotNull($appointment->fresh()->deleted_at);
    }

    /** @test */
    public function soft_deleted_appointments_are_excluded_by_default(): void
    {
        $active = Appointment::factory()->create();
        $deleted = Appointment::factory()->create();
        $deleted->delete();

        $appointments = Appointment::all();

        $this->assertCount(1, $appointments);
        $this->assertTrue($appointments->contains($active));
        $this->assertFalse($appointments->contains($deleted));
    }

    /** @test */
    public function soft_deleted_appointments_can_be_included_with_trashed(): void
    {
        Appointment::factory()->create();
        $deleted = Appointment::factory()->create();
        $deleted->delete();

        $allAppointments = Appointment::withTrashed()->get();

        $this->assertCount(2, $allAppointments);
    }

    /** @test */
    public function soft_deleted_appointment_can_be_restored(): void
    {
        $appointment = Appointment::factory()->create();
        $appointment->delete();

        $this->assertSoftDeleted('appointments', ['id' => $appointment->id]);

        $appointment->restore();

        $this->assertNull($appointment->fresh()->deleted_at);
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'deleted_at' => null]);
    }

    // ==================== New Scope Tests ====================

    /** @test */
    public function has_for_date_range_scope(): void
    {
        $from = now()->addDays(5);
        $to = now()->addDays(10);

        // Inside range
        Appointment::factory()->forDate($from->addDays(2)->toDateString())->count(2)->create();
        // Outside range
        Appointment::factory()->forDate(now()->addDays(15)->toDateString())->create();

        $this->assertCount(2, Appointment::forDateRange($from, $to)->get());
    }

    /** @test */
    public function has_not_cancelled_scope(): void
    {
        Appointment::factory()->pending()->create();
        Appointment::factory()->confirmed()->create();
        Appointment::factory()->cancelled()->create();

        $this->assertCount(2, Appointment::notCancelled()->get());
    }

    /** @test */
    public function has_awaiting_confirmation_scope(): void
    {
        Appointment::factory()->pending()->count(2)->create();
        Appointment::factory()->confirmed()->create();
        Appointment::factory()->completed()->create();

        $this->assertCount(2, Appointment::awaitingConfirmation()->get());
    }

    /** @test */
    public function patient_relationship_is_alias_for_user(): void
    {
        $patient = User::factory()->patient()->create();
        $appointment = Appointment::factory()->forPatient($patient)->create();

        $this->assertEquals($appointment->user->id, $appointment->patient->id);
        $this->assertEquals($patient->id, $appointment->patient->id);
    }
}
