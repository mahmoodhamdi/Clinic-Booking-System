<?php

namespace Tests\Feature\Api;

use App\Models\ClinicSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicClinicInfoTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function endpoint_is_public_and_returns_landing_fields(): void
    {
        ClinicSetting::create([
            'clinic_name' => 'Cairo Family Clinic',
            'doctor_name' => 'Dr. Ahmed',
            'specialization' => 'Family Medicine',
            'tagline' => 'Quality care, every visit',
            'phone' => '+201012345678',
            'email' => 'hello@example.com',
            'address' => '12 Tahrir St, Cairo',
            'about_text' => 'About the clinic',
            'services' => [
                ['title' => 'General Checkup', 'description' => '30-min appointment'],
                ['title' => 'Vaccinations', 'description' => null],
            ],
            'slot_duration' => 30,
            'max_patients_per_slot' => 1,
            'advance_booking_days' => 30,
            'cancellation_hours' => 24,
        ]);

        $response = $this->getJson('/api/public/clinic-info');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'clinic_name',
                    'doctor_name',
                    'specialization',
                    'tagline',
                    'phone',
                    'email',
                    'address',
                    'logo_url',
                    'hero_image_url',
                    'services',
                    'about_text',
                ],
            ])
            ->assertJsonPath('data.clinic_name', 'Cairo Family Clinic')
            ->assertJsonPath('data.tagline', 'Quality care, every visit')
            ->assertJsonCount(2, 'data.services');
    }

    /** @test */
    public function endpoint_does_not_expose_operational_settings(): void
    {
        ClinicSetting::create([
            'clinic_name' => 'C',
            'doctor_name' => 'D',
            'slot_duration' => 30,
            'max_patients_per_slot' => 1,
            'advance_booking_days' => 30,
            'cancellation_hours' => 24,
        ]);

        $response = $this->getJson('/api/public/clinic-info');

        $response->assertOk()
            ->assertJsonMissingPath('data.slot_duration')
            ->assertJsonMissingPath('data.max_patients_per_slot')
            ->assertJsonMissingPath('data.advance_booking_days')
            ->assertJsonMissingPath('data.cancellation_hours');
    }

    /** @test */
    public function services_default_to_empty_array_when_null(): void
    {
        ClinicSetting::create([
            'clinic_name' => 'C',
            'doctor_name' => 'D',
            'services' => null,
            'slot_duration' => 30,
            'max_patients_per_slot' => 1,
            'advance_booking_days' => 30,
            'cancellation_hours' => 24,
        ]);

        $this->getJson('/api/public/clinic-info')
            ->assertOk()
            ->assertJsonPath('data.services', []);
    }
}
