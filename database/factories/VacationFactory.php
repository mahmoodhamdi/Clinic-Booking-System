<?php

namespace Database\Factories;

use App\Models\Vacation;
use Illuminate\Database\Eloquent\Factories\Factory;

class VacationFactory extends Factory
{
    protected $model = Vacation::class;

    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('now', '+3 months');
        $endDate = fake()->dateTimeBetween($startDate, $startDate->format('Y-m-d') . ' +7 days');

        return [
            'title' => fake()->randomElement([
                'عطلة عيد الفطر',
                'عطلة عيد الأضحى',
                'إجازة سنوية',
                'مؤتمر طبي',
                'إجازة مرضية',
            ]),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reason' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Create a vacation starting today.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(3),
        ]);
    }

    /**
     * Create a vacation in the past.
     */
    public function past(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => now()->subWeeks(2),
            'end_date' => now()->subWeek(),
        ]);
    }

    /**
     * Create a vacation in the future.
     */
    public function future(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => now()->addWeek(),
            'end_date' => now()->addWeeks(2),
        ]);
    }

    /**
     * Create a single day vacation.
     */
    public function singleDay(): static
    {
        $date = fake()->dateTimeBetween('now', '+1 month');
        return $this->state(fn (array $attributes) => [
            'start_date' => $date,
            'end_date' => $date,
        ]);
    }

    /**
     * Create vacation for specific dates.
     */
    public function forDates(string $startDate, string $endDate): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }
}
