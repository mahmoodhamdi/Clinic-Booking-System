<?php

namespace Database\Factories;

use App\Models\Prescription;
use App\Models\PrescriptionItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PrescriptionItem>
 */
class PrescriptionItemFactory extends Factory
{
    protected $model = PrescriptionItem::class;

    public function definition(): array
    {
        $medications = [
            ['name' => 'أموكسيسيلين', 'dosage' => '500 مجم'],
            ['name' => 'باراسيتامول', 'dosage' => '500 مجم'],
            ['name' => 'إيبوبروفين', 'dosage' => '400 مجم'],
            ['name' => 'أوميبرازول', 'dosage' => '20 مجم'],
            ['name' => 'ميتفورمين', 'dosage' => '500 مجم'],
            ['name' => 'أملوديبين', 'dosage' => '5 مجم'],
            ['name' => 'سيتريزين', 'dosage' => '10 مجم'],
            ['name' => 'فيتامين د', 'dosage' => '1000 وحدة'],
        ];

        $frequencies = [
            'مرة واحدة يومياً',
            'مرتين يومياً',
            'ثلاث مرات يومياً',
            'كل 8 ساعات',
            'كل 12 ساعة',
            'عند اللزوم',
        ];

        $durations = [
            '3 أيام',
            '5 أيام',
            '7 أيام',
            '10 أيام',
            '14 يوم',
            'شهر',
            'شهرين',
            '3 أشهر',
        ];

        $medication = fake()->randomElement($medications);

        return [
            'prescription_id' => Prescription::factory(),
            'medication_name' => $medication['name'],
            'dosage' => $medication['dosage'],
            'frequency' => fake()->randomElement($frequencies),
            'duration' => fake()->randomElement($durations),
            'instructions' => fake()->optional()->randomElement([
                'بعد الأكل',
                'قبل الأكل',
                'مع كوب ماء كامل',
                'قبل النوم',
                'على معدة فارغة',
            ]),
            'quantity' => fake()->numberBetween(1, 30),
        ];
    }

    public function forPrescription(Prescription $prescription): static
    {
        return $this->state(fn (array $attributes) => [
            'prescription_id' => $prescription->id,
        ]);
    }
}
