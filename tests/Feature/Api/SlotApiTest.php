<?php

namespace Tests\Feature\Api;

use App\Enums\DayOfWeek;
use App\Models\ClinicSetting;
use App\Models\Schedule;
use App\Models\Vacation;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlotApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        ClinicSetting::factory()->create([
            'slot_duration' => 30,
            'advance_booking_days' => 30,
        ]);
    }

    /** @test */
    public function can_get_available_dates(): void
    {
        $tomorrow = now()->addDay();
        $dayOfWeek = DayOfWeek::fromDate($tomorrow);

        Schedule::factory()->forDay($dayOfWeek)->create(['is_active' => true]);

        $response = $this->getJson('/api/slots/dates');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => [
                    'dates',
                    'summary' => [
                        'total_days',
                        'available_dates',
                        'total_slots',
                    ],
                ],
            ]);
    }

    /** @test */
    public function can_get_available_dates_with_custom_days(): void
    {
        $response = $this->getJson('/api/slots/dates?days=7');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'summary' => [
                        'total_days' => 8, // 0-7 = 8 days
                    ],
                ],
            ]);
    }

    /** @test */
    public function can_get_slots_for_date(): void
    {
        $tomorrow = now()->addDay();
        $dayOfWeek = DayOfWeek::fromDate($tomorrow);

        Schedule::factory()->forDay($dayOfWeek)->create([
            'start_time' => '09:00',
            'end_time' => '11:00',
            'is_active' => true,
            'break_start' => null,
            'break_end' => null,
        ]);

        $response = $this->getJson('/api/slots/' . $tomorrow->toDateString());

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'date' => $tomorrow->toDateString(),
                    'is_available' => true,
                    'slots_count' => 4,
                ],
            ]);
    }

    /** @test */
    public function returns_empty_slots_for_vacation_day(): void
    {
        $tomorrow = now()->addDay()->startOfDay();
        $dayOfWeek = DayOfWeek::fromDate($tomorrow);

        Schedule::factory()->forDay($dayOfWeek)->create(['is_active' => true]);

        // Create vacation with Carbon dates directly
        Vacation::create([
            'title' => 'إجازة اختبار',
            'start_date' => $tomorrow->copy(),
            'end_date' => $tomorrow->copy(),
        ]);

        // Verify vacation was created
        $this->assertDatabaseCount('vacations', 1);

        // Verify vacation is found by the model method
        $this->assertTrue(Vacation::isVacationDay($tomorrow));

        $response = $this->getJson('/api/slots/' . $tomorrow->toDateString());

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'is_available' => false,
                    'slots_count' => 0,
                ],
            ]);
    }

    /** @test */
    public function returns_empty_slots_for_day_without_schedule(): void
    {
        $tomorrow = now()->addDay();

        // No schedule for tomorrow

        $response = $this->getJson('/api/slots/' . $tomorrow->toDateString());

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'is_available' => false,
                    'slots_count' => 0,
                ],
            ]);
    }

    /** @test */
    public function cannot_get_slots_for_past_date(): void
    {
        $yesterday = now()->subDay();

        $response = $this->getJson('/api/slots/' . $yesterday->toDateString());

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function cannot_get_slots_beyond_advance_booking_days(): void
    {
        $farFuture = now()->addDays(60);

        $response = $this->getJson('/api/slots/' . $farFuture->toDateString());

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function returns_error_for_invalid_date_format(): void
    {
        $response = $this->getJson('/api/slots/invalid-date');

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function can_check_slot_availability(): void
    {
        $tomorrow = now()->addDay()->setTime(10, 0);
        $dayOfWeek = DayOfWeek::fromDate($tomorrow);

        Schedule::factory()->forDay($dayOfWeek)->create([
            'start_time' => '09:00',
            'end_time' => '17:00',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/slots/check', [
            'datetime' => $tomorrow->toIso8601String(),
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'is_available' => true,
                ],
            ]);
    }

    /** @test */
    public function can_get_next_available_slot(): void
    {
        $tomorrow = now()->addDay();
        $dayOfWeek = DayOfWeek::fromDate($tomorrow);

        Schedule::factory()->forDay($dayOfWeek)->create([
            'start_time' => '09:00',
            'end_time' => '17:00',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/slots/next');

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    'date',
                    'day_name',
                    'time',
                    'datetime',
                ],
            ]);
    }

    /** @test */
    public function returns_error_when_no_next_slot_available(): void
    {
        // No schedules created
        $response = $this->getJson('/api/slots/next');

        $response->assertNotFound()
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function slots_exclude_break_time(): void
    {
        $tomorrow = now()->addDay();
        $dayOfWeek = DayOfWeek::fromDate($tomorrow);

        Schedule::factory()->forDay($dayOfWeek)->create([
            'start_time' => '09:00',
            'end_time' => '12:00',
            'is_active' => true,
            'break_start' => '10:00',
            'break_end' => '10:30',
        ]);

        $response = $this->getJson('/api/slots/' . $tomorrow->toDateString());

        $response->assertOk();

        $slots = $response->json('data.slots');
        $times = array_column($slots, 'time');

        $this->assertNotContains('10:00', $times);
        $this->assertContains('09:00', $times);
        $this->assertContains('09:30', $times);
        $this->assertContains('10:30', $times);
        $this->assertContains('11:00', $times);
        $this->assertContains('11:30', $times);
    }
}
