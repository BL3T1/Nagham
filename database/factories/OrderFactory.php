<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'created_by' => User::factory(),
            'status' => fake()->randomElement(['pending', 'in_progress', 'completed', 'cancelled']),
            'total_amount' => fake()->randomFloat(2, 100, 5000),
            'paid_amount' => fake()->randomFloat(2, 0, 3000),
            'remaining_amount' => fake()->randomFloat(2, 0, 3000),
            'payment_status' => fake()->randomElement(['pending', 'partial', 'paid']),
            'notes' => fake()->optional()->paragraph(),
        ];
    }

    /**
     * Indicate that the order is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'payment_status' => 'pending',
            'paid_amount' => 0,
            'remaining_amount' => $attributes['total_amount'] ?? 0,
        ]);
    }

    /**
     * Indicate that the order is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'payment_status' => 'paid',
            'paid_amount' => $attributes['total_amount'] ?? 0,
            'remaining_amount' => 0,
        ]);
    }

    /**
     * Indicate that the order is partially paid.
     */
    public function partiallyPaid(): static
    {
        return $this->state(function (array $attributes) {
            $totalAmount = $attributes['total_amount'] ?? fake()->randomFloat(2, 100, 5000);
            $paidAmount = fake()->randomFloat(2, 1, $totalAmount - 1);
            
            return [
                'payment_status' => 'partial',
                'paid_amount' => $paidAmount,
                'remaining_amount' => round($totalAmount - $paidAmount, 2),
            ];
        });
    }

    /**
     * Indicate that the order is fully paid.
     */
    public function paid(): static
    {
        return $this->state(function (array $attributes) {
            $totalAmount = $attributes['total_amount'] ?? fake()->randomFloat(2, 100, 5000);
            
            return [
                'payment_status' => 'paid',
                'paid_amount' => $totalAmount,
                'remaining_amount' => 0,
            ];
        });
    }
}

