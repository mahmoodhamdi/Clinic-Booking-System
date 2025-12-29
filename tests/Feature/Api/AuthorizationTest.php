<?php

namespace Tests\Feature\Api;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $secretary;

    private User $patient;

    private User $otherPatient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->secretary = User::factory()->secretary()->create();
        $this->patient = User::factory()->patient()->create();
        $this->otherPatient = User::factory()->patient()->create();
    }

    /** @test */
    public function patient_cannot_access_admin_routes(): void
    {
        $response = $this->actingAs($this->patient)
            ->getJson('/api/admin/dashboard/stats');

        $response->assertStatus(403);
    }

    /** @test */
    public function patient_cannot_access_admin_patients_list(): void
    {
        $response = $this->actingAs($this->patient)
            ->getJson('/api/admin/patients');

        $response->assertStatus(403);
    }

    /** @test */
    public function patient_cannot_access_admin_appointments(): void
    {
        $response = $this->actingAs($this->patient)
            ->getJson('/api/admin/appointments');

        $response->assertStatus(403);
    }

    /** @test */
    public function patient_cannot_view_other_patient_appointment(): void
    {
        $appointment = Appointment::factory()->create(['user_id' => $this->otherPatient->id]);

        $response = $this->actingAs($this->patient)
            ->getJson("/api/appointments/{$appointment->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function patient_can_view_own_appointment(): void
    {
        $appointment = Appointment::factory()->create(['user_id' => $this->patient->id]);

        $response = $this->actingAs($this->patient)
            ->getJson("/api/appointments/{$appointment->id}");

        $response->assertStatus(200);
    }

    /** @test */
    public function patient_cannot_cancel_other_patient_appointment(): void
    {
        $appointment = Appointment::factory()->create([
            'user_id' => $this->otherPatient->id,
            'status' => AppointmentStatus::PENDING,
        ]);

        $response = $this->actingAs($this->patient)
            ->postJson("/api/appointments/{$appointment->id}/cancel", [
                'reason' => 'Test cancellation reason',
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function patient_can_cancel_own_appointment(): void
    {
        $appointment = Appointment::factory()->create([
            'user_id' => $this->patient->id,
            'status' => AppointmentStatus::PENDING,
        ]);

        $response = $this->actingAs($this->patient)
            ->postJson("/api/appointments/{$appointment->id}/cancel", [
                'reason' => 'Test cancellation reason',
            ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_view_any_appointment(): void
    {
        $appointment = Appointment::factory()->create();

        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/appointments/{$appointment->id}");

        $response->assertStatus(200);
    }

    /** @test */
    public function secretary_can_view_any_appointment(): void
    {
        $appointment = Appointment::factory()->create();

        $response = $this->actingAs($this->secretary)
            ->getJson("/api/admin/appointments/{$appointment->id}");

        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_access_dashboard_stats(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/dashboard/stats');

        $response->assertStatus(200);
    }

    /** @test */
    public function secretary_can_access_dashboard_stats(): void
    {
        $response = $this->actingAs($this->secretary)
            ->getJson('/api/admin/dashboard/stats');

        $response->assertStatus(200);
    }

    /** @test */
    public function patient_cannot_view_other_patient_medical_records(): void
    {
        $appointment = Appointment::factory()->create(['user_id' => $this->otherPatient->id]);
        $record = MedicalRecord::factory()->create([
            'patient_id' => $this->otherPatient->id,
            'appointment_id' => $appointment->id,
        ]);

        $response = $this->actingAs($this->patient)
            ->getJson("/api/medical-records/{$record->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function patient_can_view_own_medical_records(): void
    {
        $appointment = Appointment::factory()->create(['user_id' => $this->patient->id]);
        $record = MedicalRecord::factory()->create([
            'patient_id' => $this->patient->id,
            'appointment_id' => $appointment->id,
        ]);

        $response = $this->actingAs($this->patient)
            ->getJson("/api/medical-records/{$record->id}");

        $response->assertStatus(200);
    }

    /** @test */
    public function patient_cannot_view_other_patient_prescription(): void
    {
        $appointment = Appointment::factory()->create(['user_id' => $this->otherPatient->id]);
        $record = MedicalRecord::factory()->create([
            'patient_id' => $this->otherPatient->id,
            'appointment_id' => $appointment->id,
        ]);
        $prescription = Prescription::factory()->create([
            'medical_record_id' => $record->id,
        ]);

        $response = $this->actingAs($this->patient)
            ->getJson("/api/prescriptions/{$prescription->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_protected_routes(): void
    {
        $response = $this->getJson('/api/patient/dashboard');
        $response->assertStatus(401);

        $response = $this->getJson('/api/appointments');
        $response->assertStatus(401);

        $response = $this->getJson('/api/admin/dashboard/stats');
        $response->assertStatus(401);
    }

    /** @test */
    public function admin_can_confirm_appointment(): void
    {
        $appointment = Appointment::factory()->create([
            'status' => AppointmentStatus::PENDING,
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/admin/appointments/{$appointment->id}/confirm");

        $response->assertStatus(200);
    }

    /** @test */
    public function patient_cannot_confirm_appointment(): void
    {
        $appointment = Appointment::factory()->create([
            'user_id' => $this->patient->id,
            'status' => AppointmentStatus::PENDING,
        ]);

        // There's no patient endpoint for confirming, so just verify admin route is blocked
        $response = $this->actingAs($this->patient)
            ->postJson("/api/admin/appointments/{$appointment->id}/confirm");

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_complete_appointment(): void
    {
        $appointment = Appointment::factory()->create([
            'status' => AppointmentStatus::CONFIRMED,
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/admin/appointments/{$appointment->id}/complete");

        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_mark_no_show(): void
    {
        $appointment = Appointment::factory()->create([
            'status' => AppointmentStatus::CONFIRMED,
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/admin/appointments/{$appointment->id}/no-show");

        $response->assertStatus(200);
    }
}
