<?php

namespace Tests\Unit\Services;

use App\Enums\DayOfWeek;
use App\Models\ClinicSetting;
use App\Models\Schedule;
use App\Models\Vacation;
use App\Services\SlotGeneratorService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlotGeneratorServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SlotGeneratorService $service;

    protected function setUp(): void
    {
        parent::setUp();

        ClinicSetting::factory()->create([
            'slot_duration' => 30,
            'advance_booking_days' => 7,
        ]);

        $this->service = new SlotGeneratorService();
    }

    /** @test */
    public function is_date_available_returns_false_for_vacation_day(): void
    {
        Schedule::factory()->forDay(DayOfWeek::SUNDAY)->create();
        Vacation::factory()->forDates(
            now()->toDateString(),
            now()->addDays(2)->toDateString()
        )->create();

        $this->assertFalse($this->service->isDateAvailable(now()));
    }

    /** @test */
    public function is_date_available_returns_false_when_no_schedule(): void
    {
        // No schedule created
        $this->assertFalse($this->service->isDateAvailable(now()));
    }

    /** @test */
    public function is_date_available_returns_false_for_inactive_schedule(): void
    {
        $today = now();
        $dayOfWeek = DayOfWeek::fromDate($today);

        Schedule::factory()->forDay($dayOfWeek)->inactive()->create();

        $this->assertFalse($this->service->isDateAvailable($today));
    }

    /** @test */
    public function is_date_available_returns_true_for_active_schedule(): void
    {
        $today = now();
        $dayOfWeek = DayOfWeek::fromDate($today);

        Schedule::factory()->forDay($dayOfWeek)->create(['is_active' => true]);

        $this->assertTrue($this->service->isDateAvailable($today));
    }

    /** @test */
    public function get_slots_for_date_returns_empty_for_vacation_day(): void
    {
        $today = now();
        $dayOfWeek = DayOfWeek::fromDate($today);

        Schedule::factory()->forDay($dayOfWeek)->create();
        Vacation::factory()->forDates(
            $today->toDateString(),
            $today->toDateString()
        )->create();

        $slots = $this->service->getSlotsForDate($today);

        $this->assertTrue($slots->isEmpty());
    }

    /** @test */
    public function get_slots_for_date_returns_empty_when_no_schedule(): void
    {
        $slots = $this->service->getSlotsForDate(now());

        $this->assertTrue($slots->isEmpty());
    }

    /** @test */
    public function get_slots_for_date_returns_slots_for_valid_day(): void
    {
        $tomorrow = now()->addDay()->startOfDay();
        $dayOfWeek = DayOfWeek::fromDate($tomorrow);

        Schedule::factory()->forDay($dayOfWeek)->create([
            'start_time' => '09:00',
            'end_time' => '11:00',
            'is_active' => true,
            'break_start' => null,
            'break_end' => null,
        ]);

        $slots = $this->service->getSlotsForDate($tomorrow);

        $this->assertCount(4, $slots);
        $this->assertEquals('09:00', $slots->first()['time']);
    }

    /** @test */
    public function get_slots_for_date_filters_past_slots_for_today(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-01-20 10:30:00'));

        $today = now();
        $dayOfWeek = DayOfWeek::fromDate($today);

        Schedule::factory()->forDay($dayOfWeek)->create([
            'start_time' => '09:00',
            'end_time' => '12:00',
            'is_active' => true,
            'break_start' => null,
            'break_end' => null,
        ]);

        $slots = $this->service->getSlotsForDate($today);

        // Should only include 11:00 and 11:30
        foreach ($slots as $slot) {
            $slotTime = Carbon::parse($slot['time']);
            $this->assertTrue($slotTime->gt(now()));
        }

        Carbon::setTestNow();
    }

    /** @test */
    public function is_slot_available_returns_false_for_unavailable_date(): void
    {
        $datetime = now()->addDay()->setTime(10, 0);

        // No schedule
        $this->assertFalse($this->service->isSlotAvailable($datetime));
    }

    /** @test */
    public function is_slot_available_returns_false_for_past_slot(): void
    {
        $pastDatetime = now()->subHour();
        $dayOfWeek = DayOfWeek::fromDate($pastDatetime);

        Schedule::factory()->forDay($dayOfWeek)->create([
            'start_time' => '09:00',
            'end_time' => '17:00',
            'is_active' => true,
        ]);

        $this->assertFalse($this->service->isSlotAvailable($pastDatetime));
    }

    /** @test */
    public function get_available_dates_returns_only_available_dates(): void
    {
        // Create schedules for some days
        Schedule::factory()->forDay(DayOfWeek::SUNDAY)->create(['is_active' => true]);
        Schedule::factory()->forDay(DayOfWeek::MONDAY)->create(['is_active' => true]);
        Schedule::factory()->forDay(DayOfWeek::FRIDAY)->inactive()->create();

        $dates = $this->service->getAvailableDates(14);

        // Should only include Sundays and Mondays
        foreach ($dates as $date) {
            $dayOfWeek = DayOfWeek::fromDate(Carbon::parse($date['date']));
            $this->assertTrue(
                in_array($dayOfWeek, [DayOfWeek::SUNDAY, DayOfWeek::MONDAY])
            );
        }
    }

    /** @test */
    public function get_next_available_slot_returns_first_available(): void
    {
        $tomorrow = now()->addDay();
        $dayOfWeek = DayOfWeek::fromDate($tomorrow);

        Schedule::factory()->forDay($dayOfWeek)->create([
            'start_time' => '09:00',
            'end_time' => '17:00',
            'is_active' => true,
        ]);

        $nextSlot = $this->service->getNextAvailableSlot();

        $this->assertNotNull($nextSlot);
        $this->assertEquals($tomorrow->toDateString(), $nextSlot['date']);
        $this->assertEquals('09:00', $nextSlot['time']);
    }

    /** @test */
    public function get_next_available_slot_returns_null_when_no_slots(): void
    {
        // No schedules created
        $nextSlot = $this->service->getNextAvailableSlot();

        $this->assertNull($nextSlot);
    }

    /** @test */
    public function get_slots_summary_returns_correct_data(): void
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

        $summary = $this->service->getSlotsSummary(7);

        $this->assertArrayHasKey('total_days', $summary);
        $this->assertArrayHasKey('available_dates', $summary);
        $this->assertArrayHasKey('total_slots', $summary);
        $this->assertArrayHasKey('next_available', $summary);
        $this->assertEquals(8, $summary['total_days']); // 0-7 = 8 days
    }

    /** @test */
    public function refresh_settings_reloads_settings(): void
    {
        $settings = ClinicSetting::getInstance();
        $settings->update(['slot_duration' => 45]);

        $this->service->refreshSettings();

        $this->assertEquals(45, $this->service->getClinicSettings()->slot_duration);
    }
}
