<?php

namespace Database\Factories;

use App\Enums\DayOfWeek;
use App\Models\Schedule;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduleFactory extends Factory
{
    protected $model = Schedule::class;

    public function definition(): array
    {
        return [
            'day_of_week' => fake()->randomElement(DayOfWeek::cases()),
            'start_time' => '09:00',
            'end_time' => '17:00',
            'is_active' => true,
            'break_start' => null,
            'break_end' => null,
        ];
    }

    /**
     * Schedule for a specific day.
     */
    public function forDay(DayOfWeek|int $day): static
    {
        $dayValue = $day instanceof DayOfWeek ? $day->value : $day;
        return $this->state(fn (array $attributes) => [
            'day_of_week' => $dayValue,
        ]);
    }

    /**
     * Morning shift schedule.
     */
    public function morning(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => '08:00',
            'end_time' => '14:00',
            'break_start' => null,
            'break_end' => null,
        ]);
    }

    /**
     * Evening shift schedule.
     */
    public function evening(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => '16:00',
            'end_time' => '22:00',
            'break_start' => null,
            'break_end' => null,
        ]);
    }

    /**
     * Full day with break schedule.
     */
    public function fullDay(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => '09:00',
            'end_time' => '18:00',
            'break_start' => '13:00',
            'break_end' => '14:00',
        ]);
    }

    /**
     * Inactive schedule.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * With break.
     */
    public function withBreak(string $breakStart = '13:00', string $breakEnd = '14:00'): static
    {
        return $this->state(fn (array $attributes) => [
            'break_start' => $breakStart,
            'break_end' => $breakEnd,
        ]);
    }
}
