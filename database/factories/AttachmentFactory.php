<?php

namespace Database\Factories;

use App\Models\Attachment;
use App\Models\MedicalRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attachment>
 */
class AttachmentFactory extends Factory
{
    protected $model = Attachment::class;

    public function definition(): array
    {
        $fileTypes = [
            ['type' => 'image', 'ext' => 'jpg', 'name' => 'أشعة'],
            ['type' => 'image', 'ext' => 'png', 'name' => 'صورة'],
            ['type' => 'pdf', 'ext' => 'pdf', 'name' => 'تقرير'],
            ['type' => 'document', 'ext' => 'docx', 'name' => 'مستند'],
        ];

        $file = fake()->randomElement($fileTypes);

        return [
            'attachable_type' => MedicalRecord::class,
            'attachable_id' => MedicalRecord::factory(),
            'file_name' => $file['name'] . '_' . fake()->unique()->randomNumber(5) . '.' . $file['ext'],
            'file_path' => 'attachments/' . fake()->uuid() . '.' . $file['ext'],
            'file_type' => $file['type'],
            'file_size' => fake()->numberBetween(10240, 5242880), // 10KB to 5MB
            'description' => fake()->optional()->sentence(),
            'uploaded_by' => User::factory(),
        ];
    }

    public function forMedicalRecord(MedicalRecord $medicalRecord): static
    {
        return $this->state(fn (array $attributes) => [
            'attachable_type' => MedicalRecord::class,
            'attachable_id' => $medicalRecord->id,
        ]);
    }

    public function uploadedBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'uploaded_by' => $user->id,
        ]);
    }

    public function image(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_name' => 'صورة_' . fake()->randomNumber(5) . '.jpg',
            'file_path' => 'attachments/' . fake()->uuid() . '.jpg',
            'file_type' => 'image',
        ]);
    }

    public function pdf(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_name' => 'تقرير_' . fake()->randomNumber(5) . '.pdf',
            'file_path' => 'attachments/' . fake()->uuid() . '.pdf',
            'file_type' => 'pdf',
        ]);
    }

    public function document(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_name' => 'مستند_' . fake()->randomNumber(5) . '.docx',
            'file_path' => 'attachments/' . fake()->uuid() . '.docx',
            'file_type' => 'document',
        ]);
    }
}
