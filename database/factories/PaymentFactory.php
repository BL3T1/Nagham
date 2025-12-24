<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'patient_id' => Patient::factory(),
            'received_by' => User::factory(),
            'amount' => fake()->randomFloat(2, 10, 5000),
            'payment_type' => fake()->randomElement(['full', 'partial', 'installment', 'refund']),
            'payment_method' => fake()->randomElement(['cash', 'card', 'bank_transfer', 'other']),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the payment is a full payment.
     */
    public function full(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_type' => 'full',
        ]);
    }

    /**
     * Indicate that the payment is a partial payment.
     */
    public function partial(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_type' => 'partial',
        ]);
    }

    /**
     * Indicate that the payment is an installment.
     */
    public function installment(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_type' => 'installment',
        ]);
    }

    /**
     * Indicate that the payment is a refund.
     */
    public function refund(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_type' => 'refund',
        ]);
    }

    /**
     * Indicate that the payment method is cash.
     */
    public function cash(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'cash',
        ]);
    }

    /**
     * Indicate that the payment method is card.
     */
    public function card(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'card',
        ]);
    }

    /**
     * Indicate that the payment method is bank transfer.
     */
    public function bankTransfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'bank_transfer',
        ]);
    }
}

