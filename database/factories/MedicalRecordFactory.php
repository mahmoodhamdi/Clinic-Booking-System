<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MedicalRecord>
 */
class MedicalRecordFactory extends Factory
{
    protected $model = MedicalRecord::class;

    public function definition(): array
    {
        $appointment = Appointment::factory()->create();

        return [
            'appointment_id' => $appointment->id,
            'patient_id' => $appointment->user_id,
            'diagnosis' => fake()->randomElement([
                'التهاب الحلق الحاد',
                'نزلة برد',
                'التهاب الجيوب الأنفية',
                'ارتفاع ضغط الدم',
                'السكري من النوع الثاني',
                'التهاب المعدة',
                'الصداع النصفي',
                'آلام أسفل الظهر',
            ]),
            'symptoms' => fake()->randomElement([
                'حمى، سعال، التهاب في الحلق',
                'صداع، دوخة، غثيان',
                'آلام في البطن، قيء',
                'ضيق في التنفس، تعب عام',
                'آلام في المفاصل، تورم',
            ]),
            'examination_notes' => fake()->optional()->sentence(),
            'treatment_plan' => fake()->optional()->paragraph(),
            'follow_up_date' => fake()->optional()->dateTimeBetween('+1 week', '+1 month'),
            'follow_up_notes' => fake()->optional()->sentence(),
            'vital_signs' => [
                'blood_pressure' => fake()->randomElement(['120/80', '130/85', '140/90', '110/70']),
                'heart_rate' => fake()->numberBetween(60, 100),
                'temperature' => fake()->randomFloat(1, 36.0, 38.5),
                'weight' => fake()->randomFloat(1, 50, 120),
                'height' => fake()->numberBetween(150, 190),
            ],
        ];
    }

    public function forAppointment(Appointment $appointment): static
    {
        return $this->state(fn (array $attributes) => [
            'appointment_id' => $appointment->id,
            'patient_id' => $appointment->user_id,
        ]);
    }

    public function forPatient(User $patient): static
    {
        return $this->state(fn (array $attributes) => [
            'patient_id' => $patient->id,
        ]);
    }

    public function withFollowUp(): static
    {
        return $this->state(fn (array $attributes) => [
            'follow_up_date' => fake()->dateTimeBetween('+1 week', '+1 month'),
            'follow_up_notes' => 'متابعة الحالة بعد العلاج',
        ]);
    }

    public function withoutVitalSigns(): static
    {
        return $this->state(fn (array $attributes) => [
            'vital_signs' => null,
        ]);
    }

    /**
     * Indicate that the medical record is soft deleted.
     */
    public function deleted(): static
    {
        return $this->state(fn(array $attributes) => [
            'deleted_at' => now(),
        ]);
    }
}
