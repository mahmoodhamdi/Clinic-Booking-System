<?php

namespace Tests\Unit\Models;

use App\Models\ClinicSetting;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClinicSettingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function clinic_setting_has_correct_fillable_attributes(): void
    {
        $fillable = [
            'clinic_name',
            'doctor_name',
            'specialization',
            'phone',
            'email',
            'address',
            'logo',
            'slot_duration',
            'max_patients_per_slot',
            'advance_booking_days',
            'cancellation_hours',
        ];

        $setting = new ClinicSetting();
        $this->assertEquals($fillable, $setting->getFillable());
    }

    /** @test */
    public function get_instance_creates_default_settings_if_none_exist(): void
    {
        $this->assertDatabaseCount('clinic_settings', 0);

        $settings = ClinicSetting::getInstance();

        $this->assertDatabaseCount('clinic_settings', 1);
        $this->assertInstanceOf(ClinicSetting::class, $settings);
        $this->assertEquals('العيادة', $settings->clinic_name);
        $this->assertEquals(30, $settings->slot_duration);
    }

    /** @test */
    public function get_instance_returns_existing_settings(): void
    {
        $created = ClinicSetting::factory()->create([
            'clinic_name' => 'عيادة التميز',
        ]);

        $settings = ClinicSetting::getInstance();

        $this->assertEquals($created->id, $settings->id);
        $this->assertEquals('عيادة التميز', $settings->clinic_name);
        $this->assertDatabaseCount('clinic_settings', 1);
    }

    /** @test */
    public function logo_url_returns_null_when_no_logo(): void
    {
        $settings = ClinicSetting::factory()->create(['logo' => null]);

        $this->assertNull($settings->logo_url);
    }

    /** @test */
    public function logo_url_returns_full_url_when_logo_exists(): void
    {
        $settings = ClinicSetting::factory()->create(['logo' => 'logos/clinic.png']);

        $this->assertNotNull($settings->logo_url);
        $this->assertStringContainsString('logos/clinic.png', $settings->logo_url);
    }

    /** @test */
    public function get_max_booking_date_returns_correct_date(): void
    {
        $settings = ClinicSetting::factory()->create(['advance_booking_days' => 30]);

        $maxDate = $settings->getMaxBookingDate();

        $this->assertEquals(
            now()->addDays(30)->toDateString(),
            $maxDate->toDateString()
        );
    }

    /** @test */
    public function get_cancellation_deadline_returns_correct_time(): void
    {
        $settings = ClinicSetting::factory()->create(['cancellation_hours' => 24]);
        $appointmentTime = Carbon::parse('2025-01-20 10:00:00');

        $deadline = $settings->getCancellationDeadline($appointmentTime);

        $this->assertEquals(
            '2025-01-19 10:00:00',
            $deadline->format('Y-m-d H:i:s')
        );
    }

    /** @test */
    public function can_cancel_appointment_returns_true_before_deadline(): void
    {
        $settings = ClinicSetting::factory()->create(['cancellation_hours' => 24]);
        $appointmentTime = now()->addDays(2);

        $this->assertTrue($settings->canCancelAppointment($appointmentTime));
    }

    /** @test */
    public function can_cancel_appointment_returns_false_after_deadline(): void
    {
        $settings = ClinicSetting::factory()->create(['cancellation_hours' => 24]);
        $appointmentTime = now()->addHours(12);

        $this->assertFalse($settings->canCancelAppointment($appointmentTime));
    }

    /** @test */
    public function casts_slot_duration_to_integer(): void
    {
        $settings = ClinicSetting::factory()->create(['slot_duration' => '30']);

        $this->assertIsInt($settings->slot_duration);
        $this->assertEquals(30, $settings->slot_duration);
    }
}
