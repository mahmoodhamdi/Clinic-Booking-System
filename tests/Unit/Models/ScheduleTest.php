<?php

namespace Tests\Unit\Models;

use App\Enums\DayOfWeek;
use App\Models\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function schedule_has_correct_fillable_attributes(): void
    {
        $fillable = [
            'day_of_week',
            'start_time',
            'end_time',
            'is_active',
            'break_start',
            'break_end',
        ];

        $schedule = new Schedule();
        $this->assertEquals($fillable, $schedule->getFillable());
    }

    /** @test */
    public function day_of_week_is_cast_to_enum(): void
    {
        $schedule = Schedule::factory()->forDay(DayOfWeek::SUNDAY)->create();

        $this->assertInstanceOf(DayOfWeek::class, $schedule->day_of_week);
        $this->assertEquals(DayOfWeek::SUNDAY, $schedule->day_of_week);
    }

    /** @test */
    public function is_active_is_cast_to_boolean(): void
    {
        $schedule = Schedule::factory()->create(['is_active' => 1]);

        $this->assertIsBool($schedule->is_active);
        $this->assertTrue($schedule->is_active);
    }

    /** @test */
    public function scope_active_returns_only_active_schedules(): void
    {
        Schedule::factory()->forDay(DayOfWeek::SUNDAY)->create(['is_active' => true]);
        Schedule::factory()->forDay(DayOfWeek::MONDAY)->inactive()->create();

        $active = Schedule::active()->get();

        $this->assertCount(1, $active);
        $this->assertEquals(DayOfWeek::SUNDAY->value, $active->first()->day_of_week->value);
    }

    /** @test */
    public function scope_for_day_returns_schedule_for_specific_day(): void
    {
        Schedule::factory()->forDay(DayOfWeek::SUNDAY)->create();
        Schedule::factory()->forDay(DayOfWeek::MONDAY)->create();

        $schedule = Schedule::forDay(DayOfWeek::SUNDAY)->first();

        $this->assertNotNull($schedule);
        $this->assertEquals(DayOfWeek::SUNDAY, $schedule->day_of_week);
    }

    /** @test */
    public function day_name_returns_arabic_name(): void
    {
        $schedule = Schedule::factory()->forDay(DayOfWeek::SUNDAY)->create();

        $this->assertEquals('الأحد', $schedule->day_name);
    }

    /** @test */
    public function day_name_en_returns_english_name(): void
    {
        $schedule = Schedule::factory()->forDay(DayOfWeek::SUNDAY)->create();

        $this->assertEquals('Sunday', $schedule->day_name_en);
    }

    /** @test */
    public function has_break_returns_true_when_break_exists(): void
    {
        $schedule = Schedule::factory()->withBreak('13:00', '14:00')->create();

        $this->assertTrue($schedule->hasBreak());
    }

    /** @test */
    public function has_break_returns_false_when_no_break(): void
    {
        $schedule = Schedule::factory()->create([
            'break_start' => null,
            'break_end' => null,
        ]);

        $this->assertFalse($schedule->hasBreak());
    }

    /** @test */
    public function generate_slots_returns_correct_slots_without_break(): void
    {
        $schedule = Schedule::factory()->create([
            'start_time' => '09:00',
            'end_time' => '11:00',
            'break_start' => null,
            'break_end' => null,
        ]);

        $slots = $schedule->generateSlots(30);

        $this->assertCount(4, $slots);
        $this->assertEquals(['09:00', '09:30', '10:00', '10:30'], $slots->toArray());
    }

    /** @test */
    public function generate_slots_excludes_break_time(): void
    {
        $schedule = Schedule::factory()->create([
            'start_time' => '09:00',
            'end_time' => '12:00',
            'break_start' => '10:00',
            'break_end' => '10:30',
        ]);

        $slots = $schedule->generateSlots(30);

        $this->assertNotContains('10:00', $slots->toArray());
        $this->assertContains('09:00', $slots->toArray());
        $this->assertContains('09:30', $slots->toArray());
        $this->assertContains('10:30', $slots->toArray());
        $this->assertContains('11:00', $slots->toArray());
        $this->assertContains('11:30', $slots->toArray());
    }

    /** @test */
    public function get_slots_count_returns_correct_count(): void
    {
        $schedule = Schedule::factory()->create([
            'start_time' => '09:00',
            'end_time' => '12:00',
            'break_start' => null,
            'break_end' => null,
        ]);

        $count = $schedule->getSlotsCount(30);

        $this->assertEquals(6, $count);
    }

    /** @test */
    public function formatted_start_time_returns_correct_format(): void
    {
        $schedule = Schedule::factory()->create(['start_time' => '09:00']);

        $this->assertEquals('09:00', $schedule->formatted_start_time);
    }

    /** @test */
    public function formatted_break_start_returns_null_when_no_break(): void
    {
        $schedule = Schedule::factory()->create(['break_start' => null]);

        $this->assertNull($schedule->formatted_break_start);
    }
}
