<?php

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Enums\CancelledBy;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    public function definition(): array
    {
        $date = $this->faker->dateTimeBetween('now', '+30 days');
        $times = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '14:00', '14:30', '15:00', '15:30', '16:00'];

        return [
            'user_id' => User::factory()->patient(),
            'appointment_date' => $date->format('Y-m-d'),
            'appointment_time' => $this->faker->randomElement($times),
            'status' => AppointmentStatus::PENDING,
            'notes' => $this->faker->optional(0.3)->sentence(),
            'admin_notes' => null,
            'cancellation_reason' => null,
            'cancelled_by' => null,
            'cancelled_at' => null,
            'confirmed_at' => null,
            'completed_at' => null,
        ];
    }

    public function forPatient(User $patient): static
    {
        return $this->state(fn(array $attributes) => [
            'user_id' => $patient->id,
        ]);
    }

    public function forDate(string $date): static
    {
        return $this->state(fn(array $attributes) => [
            'appointment_date' => $date,
        ]);
    }

    public function forTime(string $time): static
    {
        return $this->state(fn(array $attributes) => [
            'appointment_time' => $time,
        ]);
    }

    public function forDateTime(string $date, string $time): static
    {
        return $this->state(fn(array $attributes) => [
            'appointment_date' => $date,
            'appointment_time' => $time,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => AppointmentStatus::PENDING,
        ]);
    }

    public function confirmed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => AppointmentStatus::CONFIRMED,
            'confirmed_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => AppointmentStatus::COMPLETED,
            'confirmed_at' => now()->subHour(),
            'completed_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => AppointmentStatus::CANCELLED,
            'cancellation_reason' => $this->faker->sentence(),
            'cancelled_by' => $this->faker->randomElement(CancelledBy::cases()),
            'cancelled_at' => now(),
        ]);
    }

    public function cancelledByPatient(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => AppointmentStatus::CANCELLED,
            'cancellation_reason' => $this->faker->sentence(),
            'cancelled_by' => CancelledBy::PATIENT,
            'cancelled_at' => now(),
        ]);
    }

    public function cancelledByAdmin(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => AppointmentStatus::CANCELLED,
            'cancellation_reason' => $this->faker->sentence(),
            'cancelled_by' => CancelledBy::ADMIN,
            'cancelled_at' => now(),
        ]);
    }

    public function noShow(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => AppointmentStatus::NO_SHOW,
        ]);
    }

    public function today(): static
    {
        return $this->state(fn(array $attributes) => [
            'appointment_date' => now()->toDateString(),
        ]);
    }

    public function tomorrow(): static
    {
        return $this->state(fn(array $attributes) => [
            'appointment_date' => now()->addDay()->toDateString(),
        ]);
    }

    public function past(): static
    {
        $date = $this->faker->dateTimeBetween('-30 days', '-1 day');
        return $this->state(fn(array $attributes) => [
            'appointment_date' => $date->format('Y-m-d'),
        ]);
    }

    public function future(): static
    {
        $date = $this->faker->dateTimeBetween('+1 day', '+30 days');
        return $this->state(fn(array $attributes) => [
            'appointment_date' => $date->format('Y-m-d'),
        ]);
    }

    public function withNotes(string $notes = null): static
    {
        return $this->state(fn(array $attributes) => [
            'notes' => $notes ?? $this->faker->paragraph(),
        ]);
    }

    public function withAdminNotes(string $notes = null): static
    {
        return $this->state(fn(array $attributes) => [
            'admin_notes' => $notes ?? $this->faker->paragraph(),
        ]);
    }

    /**
     * Indicate that the appointment is soft deleted.
     */
    public function deleted(): static
    {
        return $this->state(fn(array $attributes) => [
            'deleted_at' => now(),
        ]);
    }
}
