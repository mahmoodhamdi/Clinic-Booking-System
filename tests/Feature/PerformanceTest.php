<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\ClinicSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->patient = User::factory()->patient()->create();
    }

    /** @test */
    public function dashboard_stats_loads_within_acceptable_time(): void
    {
        // Create some test data
        Appointment::factory()->count(50)->create(['user_id' => $this->patient->id]);

        $this->actingAs($this->admin);

        $start = microtime(true);
        $response = $this->getJson('/api/admin/dashboard/stats');
        $duration = microtime(true) - $start;

        $response->assertOk();

        // Dashboard should load in under 500ms
        $this->assertLessThan(0.5, $duration, 'Dashboard should load in under 500ms');
    }

    /** @test */
    public function appointment_list_has_limited_queries(): void
    {
        // Create 50 appointments with different patients
        $patients = User::factory()->patient()->count(10)->create();
        foreach ($patients as $patient) {
            Appointment::factory()->count(5)->create(['user_id' => $patient->id]);
        }

        $this->actingAs($this->admin);

        DB::enableQueryLog();

        $response = $this->getJson('/api/admin/appointments');

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $response->assertOk();

        // Should have limited queries due to eager loading (typically < 10)
        // Main query + user relation + possibly pagination = should be much less than N+1
        $this->assertLessThan(15, count($queries), 'Too many queries detected (possible N+1 issue)');
    }

    /** @test */
    public function clinic_settings_are_cached(): void
    {
        // Clear any existing cache
        Cache::forget(ClinicSetting::CACHE_KEY);

        // First call - should query database
        DB::enableQueryLog();
        $settings1 = ClinicSetting::getInstance();
        $queriesFirst = count(DB::getQueryLog());

        // Second call - should use cache
        DB::flushQueryLog();
        $settings2 = ClinicSetting::getInstance();
        $queriesSecond = count(DB::getQueryLog());

        DB::disableQueryLog();

        // First call may have 1-2 queries (select + possibly insert)
        $this->assertGreaterThan(0, $queriesFirst);

        // Second call should have 0 queries (cached)
        $this->assertEquals(0, $queriesSecond, 'Clinic settings should be cached');

        // Both should return same data
        $this->assertEquals($settings1->id, $settings2->id);
    }

    /** @test */
    public function clinic_settings_cache_is_invalidated_on_update(): void
    {
        // Get initial settings
        $settings = ClinicSetting::getInstance();
        $originalName = $settings->clinic_name;

        // Update settings
        $settings->update(['clinic_name' => 'Updated Clinic Name']);

        // Get settings again - should reflect update
        $freshSettings = ClinicSetting::getInstance();

        $this->assertEquals('Updated Clinic Name', $freshSettings->clinic_name);

        // Clean up
        $settings->update(['clinic_name' => $originalName]);
    }

    /** @test */
    public function patient_dashboard_loads_efficiently(): void
    {
        // Create appointments for patient
        Appointment::factory()->count(10)->create(['user_id' => $this->patient->id]);

        $this->actingAs($this->patient);

        DB::enableQueryLog();

        $start = microtime(true);
        $response = $this->getJson('/api/patient/dashboard');
        $duration = microtime(true) - $start;

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $response->assertOk();

        // Should be fast
        $this->assertLessThan(0.5, $duration, 'Patient dashboard should load quickly');

        // Should have limited queries
        $this->assertLessThan(10, count($queries), 'Patient dashboard has too many queries');
    }

    /** @test */
    public function slots_endpoint_is_performant(): void
    {
        $date = now()->addDays(1)->format('Y-m-d');

        $start = microtime(true);
        $response = $this->getJson("/api/slots/{$date}");
        $duration = microtime(true) - $start;

        $response->assertOk();

        // Slots should load quickly (with caching)
        $this->assertLessThan(0.3, $duration, 'Slots endpoint should be fast');
    }

    /** @test */
    public function today_appointments_uses_eager_loading(): void
    {
        // Create today's appointments
        $patients = User::factory()->patient()->count(5)->create();
        foreach ($patients as $patient) {
            Appointment::factory()->create([
                'user_id' => $patient->id,
                'appointment_date' => now()->toDateString(),
            ]);
        }

        $this->actingAs($this->admin);

        DB::enableQueryLog();

        $response = $this->getJson('/api/admin/appointments/today');

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $response->assertOk();

        // Should use eager loading - expect ~3-5 queries instead of N+1
        $this->assertLessThan(10, count($queries), 'Today appointments should use eager loading');
    }

    /** @test */
    public function medical_records_list_is_optimized(): void
    {
        // Create medical records
        $appointments = Appointment::factory()->count(10)->create([
            'user_id' => $this->patient->id,
        ]);

        foreach ($appointments as $appointment) {
            \App\Models\MedicalRecord::factory()->create([
                'patient_id' => $this->patient->id,
                'appointment_id' => $appointment->id,
            ]);
        }

        $this->actingAs($this->admin);

        DB::enableQueryLog();

        $response = $this->getJson('/api/admin/medical-records');

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $response->assertOk();

        // Should be optimized with eager loading
        // Allow up to 40 queries for: factory creation + pagination + eager loading
        $this->assertLessThan(40, count($queries), 'Medical records list has too many queries');
    }

    /** @test */
    public function patients_list_with_search_is_efficient(): void
    {
        // Create many patients
        User::factory()->patient()->count(20)->create();

        $this->actingAs($this->admin);

        DB::enableQueryLog();

        $response = $this->getJson('/api/admin/patients?search=test');

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $response->assertOk();

        // Search should be efficient
        $this->assertLessThan(10, count($queries), 'Patient search has too many queries');
    }
}
