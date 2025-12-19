<?php

namespace Database\Factories;

use App\Models\ClinicSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClinicSettingFactory extends Factory
{
    protected $model = ClinicSetting::class;

    public function definition(): array
    {
        return [
            'clinic_name' => 'عيادة ' . fake()->lastName(),
            'doctor_name' => 'د. ' . fake()->name(),
            'specialization' => fake()->randomElement(['طب عام', 'طب أطفال', 'طب باطني', 'جراحة', 'عظام']),
            'phone' => '010' . fake()->numerify('########'),
            'email' => fake()->unique()->safeEmail(),
            'address' => fake()->address(),
            'logo' => null,
            'slot_duration' => fake()->randomElement([15, 20, 30, 45, 60]),
            'max_patients_per_slot' => fake()->randomElement([1, 2, 3]),
            'advance_booking_days' => fake()->randomElement([7, 14, 30, 60]),
            'cancellation_hours' => fake()->randomElement([12, 24, 48]),
        ];
    }

    /**
     * Default clinic settings.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'clinic_name' => 'عيادة الشفاء',
            'doctor_name' => 'د. أحمد محمد',
            'specialization' => 'طب عام',
            'slot_duration' => 30,
            'max_patients_per_slot' => 1,
            'advance_booking_days' => 30,
            'cancellation_hours' => 24,
        ]);
    }
}
