<?php

namespace Database\Factories;

use App\Models\MedicalRecord;
use App\Models\Prescription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Prescription>
 */
class PrescriptionFactory extends Factory
{
    protected $model = Prescription::class;

    public function definition(): array
    {
        return [
            'medical_record_id' => MedicalRecord::factory(),
            'prescription_number' => null, // Will be auto-generated
            'notes' => fake()->optional()->sentence(),
            'valid_until' => fake()->dateTimeBetween('+1 week', '+3 months'),
            'is_dispensed' => false,
            'dispensed_at' => null,
        ];
    }

    public function forMedicalRecord(MedicalRecord $medicalRecord): static
    {
        return $this->state(fn (array $attributes) => [
            'medical_record_id' => $medicalRecord->id,
        ]);
    }

    public function dispensed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_dispensed' => true,
            'dispensed_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'valid_until' => fake()->dateTimeBetween('-1 month', '-1 day'),
        ]);
    }

    public function valid(): static
    {
        return $this->state(fn (array $attributes) => [
            'valid_until' => fake()->dateTimeBetween('+1 week', '+3 months'),
        ]);
    }

    public function noExpiry(): static
    {
        return $this->state(fn (array $attributes) => [
            'valid_until' => null,
        ]);
    }
}
