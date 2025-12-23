<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Appointment;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 50, 500);
        $discount = fake()->optional(0.3)->randomFloat(2, 0, $amount * 0.2);
        $discount = $discount ?? 0;

        return [
            'appointment_id' => Appointment::factory(),
            'amount' => $amount,
            'discount' => $discount,
            'total' => Payment::calculateTotal($amount, $discount),
            'method' => fake()->randomElement(PaymentMethod::cases()),
            'status' => PaymentStatus::PENDING,
            'transaction_id' => null,
            'notes' => fake()->optional()->sentence(),
            'paid_at' => null,
        ];
    }

    public function forAppointment(Appointment $appointment): static
    {
        return $this->state(fn (array $attributes) => [
            'appointment_id' => $appointment->id,
        ]);
    }

    public function cash(): static
    {
        return $this->state(fn (array $attributes) => [
            'method' => PaymentMethod::CASH,
        ]);
    }

    public function card(): static
    {
        return $this->state(fn (array $attributes) => [
            'method' => PaymentMethod::CARD,
        ]);
    }

    public function wallet(): static
    {
        return $this->state(fn (array $attributes) => [
            'method' => PaymentMethod::WALLET,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::PENDING,
            'paid_at' => null,
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::PAID,
            'paid_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'transaction_id' => 'TXN-' . fake()->unique()->randomNumber(8),
        ]);
    }

    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::REFUNDED,
            'paid_at' => fake()->dateTimeBetween('-1 month', '-1 week'),
            'refunded_at' => fake()->dateTimeBetween('-1 week', 'now'),
            'notes' => 'تم الاسترداد',
        ]);
    }

    public function withDiscount(float $discount): static
    {
        return $this->state(function (array $attributes) use ($discount) {
            return [
                'discount' => $discount,
                'total' => Payment::calculateTotal($attributes['amount'], $discount),
            ];
        });
    }

    public function amount(float $amount): static
    {
        return $this->state(function (array $attributes) use ($amount) {
            return [
                'amount' => $amount,
                'total' => Payment::calculateTotal($amount, $attributes['discount'] ?? 0),
            ];
        });
    }

    /**
     * Indicate that the payment is soft deleted.
     */
    public function deleted(): static
    {
        return $this->state(fn(array $attributes) => [
            'deleted_at' => now(),
        ]);
    }
}
