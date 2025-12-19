<?php

namespace Tests\Unit\Models;

use App\Models\Vacation;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VacationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function vacation_has_correct_fillable_attributes(): void
    {
        $fillable = ['title', 'start_date', 'end_date', 'reason'];

        $vacation = new Vacation();
        $this->assertEquals($fillable, $vacation->getFillable());
    }

    /** @test */
    public function dates_are_cast_to_carbon_instances(): void
    {
        $vacation = Vacation::factory()->create([
            'start_date' => '2025-01-20',
            'end_date' => '2025-01-25',
        ]);

        $this->assertInstanceOf(Carbon::class, $vacation->start_date);
        $this->assertInstanceOf(Carbon::class, $vacation->end_date);
    }

    /** @test */
    public function scope_upcoming_returns_future_and_current_vacations(): void
    {
        Vacation::factory()->past()->create();
        Vacation::factory()->active()->create();
        Vacation::factory()->future()->create();

        $upcoming = Vacation::upcoming()->get();

        $this->assertCount(2, $upcoming);
    }

    /** @test */
    public function scope_active_returns_only_current_vacations(): void
    {
        Vacation::factory()->past()->create();
        Vacation::factory()->active()->create();
        Vacation::factory()->future()->create();

        $active = Vacation::active()->get();

        $this->assertCount(1, $active);
    }

    /** @test */
    public function scope_for_date_returns_vacations_including_date(): void
    {
        $vacation = Vacation::factory()->forDates('2025-01-20', '2025-01-25')->create();

        $found = Vacation::forDate('2025-01-22')->first();

        $this->assertNotNull($found);
        $this->assertEquals($vacation->id, $found->id);
    }

    /** @test */
    public function scope_for_date_returns_empty_for_date_outside_vacation(): void
    {
        Vacation::factory()->forDates('2025-01-20', '2025-01-25')->create();

        $found = Vacation::forDate('2025-01-26')->first();

        $this->assertNull($found);
    }

    /** @test */
    public function is_active_returns_true_for_current_vacation(): void
    {
        $vacation = Vacation::factory()->active()->create();

        $this->assertTrue($vacation->isActive());
    }

    /** @test */
    public function is_active_returns_false_for_past_vacation(): void
    {
        $vacation = Vacation::factory()->past()->create();

        $this->assertFalse($vacation->isActive());
    }

    /** @test */
    public function is_future_returns_true_for_future_vacation(): void
    {
        $vacation = Vacation::factory()->future()->create();

        $this->assertTrue($vacation->isFuture());
    }

    /** @test */
    public function is_past_returns_true_for_past_vacation(): void
    {
        $vacation = Vacation::factory()->past()->create();

        $this->assertTrue($vacation->isPast());
    }

    /** @test */
    public function includes_date_returns_true_for_date_in_range(): void
    {
        $vacation = Vacation::factory()->forDates('2025-01-20', '2025-01-25')->create();

        $this->assertTrue($vacation->includesDate('2025-01-22'));
        $this->assertTrue($vacation->includesDate('2025-01-20'));
        $this->assertTrue($vacation->includesDate('2025-01-25'));
    }

    /** @test */
    public function includes_date_returns_false_for_date_outside_range(): void
    {
        $vacation = Vacation::factory()->forDates('2025-01-20', '2025-01-25')->create();

        $this->assertFalse($vacation->includesDate('2025-01-19'));
        $this->assertFalse($vacation->includesDate('2025-01-26'));
    }

    /** @test */
    public function overlaps_returns_true_for_overlapping_ranges(): void
    {
        $vacation = Vacation::factory()->forDates('2025-01-20', '2025-01-25')->create();

        $this->assertTrue($vacation->overlaps('2025-01-22', '2025-01-28'));
        $this->assertTrue($vacation->overlaps('2025-01-15', '2025-01-22'));
        $this->assertTrue($vacation->overlaps('2025-01-21', '2025-01-24'));
    }

    /** @test */
    public function overlaps_returns_false_for_non_overlapping_ranges(): void
    {
        $vacation = Vacation::factory()->forDates('2025-01-20', '2025-01-25')->create();

        $this->assertFalse($vacation->overlaps('2025-01-10', '2025-01-19'));
        $this->assertFalse($vacation->overlaps('2025-01-26', '2025-01-30'));
    }

    /** @test */
    public function days_count_returns_correct_count(): void
    {
        $vacation = Vacation::factory()->forDates('2025-01-20', '2025-01-25')->create();

        $this->assertEquals(6, $vacation->days_count);
    }

    /** @test */
    public function days_count_returns_one_for_single_day(): void
    {
        $vacation = Vacation::factory()->singleDay()->create();

        $this->assertEquals(1, $vacation->days_count);
    }

    /** @test */
    public function is_vacation_day_returns_true_for_vacation_date(): void
    {
        Vacation::factory()->forDates('2025-01-20', '2025-01-25')->create();

        $this->assertTrue(Vacation::isVacationDay('2025-01-22'));
    }

    /** @test */
    public function is_vacation_day_returns_false_for_non_vacation_date(): void
    {
        Vacation::factory()->forDates('2025-01-20', '2025-01-25')->create();

        $this->assertFalse(Vacation::isVacationDay('2025-01-26'));
    }
}
