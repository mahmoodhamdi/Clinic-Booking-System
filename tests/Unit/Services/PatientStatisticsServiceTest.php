<?php

namespace Tests\Unit\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\User;
use App\Services\PatientStatisticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientStatisticsServiceTest extends TestCase
{
    use RefreshDatabase;

    private PatientStatisticsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PatientStatisticsService();
    }

    /** @test */
    public function calculates_correct_statistics_for_patient(): void
    {
        $patient = User::factory()->patient()->create();

        Appointment::factory()->count(3)->create([
            'user_id' => $patient->id,
            'status' => AppointmentStatus::COMPLETED,
        ]);

        Appointment::factory()->count(2)->create([
            'user_id' => $patient->id,
            'status' => AppointmentStatus::CANCELLED,
        ]);

        Appointment::factory()->create([
            'user_id' => $patient->id,
            'status' => AppointmentStatus::NO_SHOW,
        ]);

        $stats = $this->service->getForPatient($patient);

        $this->assertEquals(6, $stats['total_appointments']);
        $this->assertEquals(3, $stats['completed_appointments']);
        $this->assertEquals(2, $stats['cancelled_appointments']);
        $this->assertEquals(1, $stats['no_show_count']);
    }

    /** @test */
    public function calculates_upcoming_appointments(): void
    {
        $patient = User::factory()->patient()->create();

        // Past appointments
        Appointment::factory()->count(2)->create([
            'user_id' => $patient->id,
            'status' => AppointmentStatus::COMPLETED,
            'appointment_date' => now()->subDays(5),
        ]);

        // Future pending appointments
        Appointment::factory()->count(3)->create([
            'user_id' => $patient->id,
            'status' => AppointmentStatus::PENDING,
            'appointment_date' => now()->addDays(5),
        ]);

        // Future confirmed appointments
        Appointment::factory()->count(2)->create([
            'user_id' => $patient->id,
            'status' => AppointmentStatus::CONFIRMED,
            'appointment_date' => now()->addDays(3),
        ]);

        $stats = $this->service->getForPatient($patient);

        $this->assertEquals(7, $stats['total_appointments']);
        $this->assertEquals(5, $stats['upcoming_appointments']);
    }

    /** @test */
    public function returns_last_visit_date(): void
    {
        $patient = User::factory()->patient()->create();

        $lastVisitDate = now()->subDays(3)->format('Y-m-d');

        Appointment::factory()->create([
            'user_id' => $patient->id,
            'status' => AppointmentStatus::COMPLETED,
            'appointment_date' => now()->subDays(10),
        ]);

        Appointment::factory()->create([
            'user_id' => $patient->id,
            'status' => AppointmentStatus::COMPLETED,
            'appointment_date' => $lastVisitDate,
        ]);

        $stats = $this->service->getForPatient($patient);

        // The service may return date with time, so we just check the date part
        $this->assertStringStartsWith($lastVisitDate, $stats['last_visit']);
    }

    /** @test */
    public function returns_null_last_visit_for_new_patient(): void
    {
        $patient = User::factory()->patient()->create();

        $stats = $this->service->getForPatient($patient);

        $this->assertNull($stats['last_visit']);
        $this->assertEquals(0, $stats['total_appointments']);
    }

    /** @test */
    public function batch_statistics_returns_correct_data(): void
    {
        $patients = User::factory()->patient()->count(3)->create();

        foreach ($patients as $index => $patient) {
            Appointment::factory()->count($index + 1)->create([
                'user_id' => $patient->id,
                'status' => AppointmentStatus::COMPLETED,
            ]);
        }

        $stats = $this->service->getForPatients($patients);

        $this->assertCount(3, $stats);

        foreach ($patients as $index => $patient) {
            $this->assertEquals($index + 1, $stats[$patient->id]['total_appointments']);
        }
    }

    /** @test */
    public function batch_statistics_handles_empty_collection(): void
    {
        $patients = collect();

        $stats = $this->service->getForPatients($patients);

        $this->assertEmpty($stats);
    }

    /** @test */
    public function batch_statistics_handles_patients_with_no_appointments(): void
    {
        $patients = User::factory()->patient()->count(3)->create();

        // Only first patient has appointments
        Appointment::factory()->count(5)->create([
            'user_id' => $patients[0]->id,
            'status' => AppointmentStatus::COMPLETED,
        ]);

        $stats = $this->service->getForPatients($patients);

        $this->assertEquals(5, $stats[$patients[0]->id]['total_appointments']);
        $this->assertEquals(0, $stats[$patients[1]->id]['total_appointments']);
        $this->assertEquals(0, $stats[$patients[2]->id]['total_appointments']);
    }

    /** @test */
    public function excludes_soft_deleted_appointments(): void
    {
        $patient = User::factory()->patient()->create();

        Appointment::factory()->count(3)->create([
            'user_id' => $patient->id,
            'status' => AppointmentStatus::COMPLETED,
        ]);

        // Soft delete one appointment
        Appointment::where('user_id', $patient->id)->first()->delete();

        $stats = $this->service->getForPatient($patient);

        $this->assertEquals(2, $stats['total_appointments']);
    }
}
