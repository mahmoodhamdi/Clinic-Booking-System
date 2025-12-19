<?php

namespace Database\Factories;

use App\Enums\BloodType;
use App\Models\PatientProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PatientProfileFactory extends Factory
{
    protected $model = PatientProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->patient(),
            'blood_type' => $this->faker->randomElement(BloodType::cases()),
            'emergency_contact_name' => $this->faker->name(),
            'emergency_contact_phone' => '+20' . $this->faker->numerify('1#########'),
            'allergies' => $this->faker->optional(0.3)->randomElements(
                ['البنسلين', 'الأسبرين', 'السلفا', 'اللاتكس', 'الغلوتين'],
                $this->faker->numberBetween(1, 3)
            ),
            'chronic_diseases' => $this->faker->optional(0.3)->randomElements(
                ['السكري', 'ضغط الدم', 'أمراض القلب', 'الربو', 'الكولسترول'],
                $this->faker->numberBetween(1, 2)
            ),
            'current_medications' => $this->faker->optional(0.3)->randomElements(
                ['ميتفورمين 500mg', 'أملوديبين 5mg', 'أتورفاستاتين 20mg', 'أوميبرازول 20mg'],
                $this->faker->numberBetween(1, 2)
            ),
            'medical_notes' => $this->faker->optional(0.2)->paragraph(),
            'insurance_provider' => $this->faker->optional(0.5)->company(),
            'insurance_number' => $this->faker->optional(0.5)->numerify('INS-######'),
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn(array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    public function withBloodType(BloodType $bloodType): static
    {
        return $this->state(fn(array $attributes) => [
            'blood_type' => $bloodType,
        ]);
    }

    public function withEmergencyContact(string $name = null, string $phone = null): static
    {
        return $this->state(fn(array $attributes) => [
            'emergency_contact_name' => $name ?? $this->faker->name(),
            'emergency_contact_phone' => $phone ?? '+20' . $this->faker->numerify('1#########'),
        ]);
    }

    public function withoutEmergencyContact(): static
    {
        return $this->state(fn(array $attributes) => [
            'emergency_contact_name' => null,
            'emergency_contact_phone' => null,
        ]);
    }

    public function withAllergies(array $allergies): static
    {
        return $this->state(fn(array $attributes) => [
            'allergies' => $allergies,
        ]);
    }

    public function withChronicDiseases(array $diseases): static
    {
        return $this->state(fn(array $attributes) => [
            'chronic_diseases' => $diseases,
        ]);
    }

    public function withMedications(array $medications): static
    {
        return $this->state(fn(array $attributes) => [
            'current_medications' => $medications,
        ]);
    }

    public function withInsurance(string $provider = null, string $number = null): static
    {
        return $this->state(fn(array $attributes) => [
            'insurance_provider' => $provider ?? $this->faker->company(),
            'insurance_number' => $number ?? $this->faker->numerify('INS-######'),
        ]);
    }

    public function withoutInsurance(): static
    {
        return $this->state(fn(array $attributes) => [
            'insurance_provider' => null,
            'insurance_number' => null,
        ]);
    }

    public function complete(): static
    {
        return $this->state(fn(array $attributes) => [
            'blood_type' => $this->faker->randomElement(BloodType::cases()),
            'emergency_contact_name' => $this->faker->name(),
            'emergency_contact_phone' => '+20' . $this->faker->numerify('1#########'),
        ]);
    }

    public function incomplete(): static
    {
        return $this->state(fn(array $attributes) => [
            'blood_type' => null,
            'emergency_contact_name' => null,
            'emergency_contact_phone' => null,
        ]);
    }
}
