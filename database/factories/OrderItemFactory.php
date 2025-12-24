<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = OrderItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $price = fake()->randomFloat(2, 50, 2000);
        $eligibleForInstallment = fake()->boolean(30); // 30% chance
        
        return [
            'order_id' => Order::factory(),
            'doctor_id' => User::factory(),
            'status' => fake()->randomElement(['pending', 'in_progress', 'completed', 'cancelled']),
            'price' => $price,
            'eligible_for_installment' => $eligibleForInstallment,
            'down_payment' => $eligibleForInstallment ? fake()->randomFloat(2, $price * 0.1, $price * 0.5) : null,
            'notes' => fake()->optional()->sentence(),
            'next_session_date' => fake()->optional(0.7)->dateTimeBetween('now', '+30 days'),
        ];
    }

    /**
     * Indicate that the order item is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Indicate that the order item is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
        ]);
    }

    /**
     * Indicate that the order item is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }

    /**
     * Indicate that the order item is eligible for installment.
     */
    public function withInstallment(): static
    {
        return $this->state(function (array $attributes) {
            $price = $attributes['price'] ?? fake()->randomFloat(2, 100, 2000);
            
            return [
                'eligible_for_installment' => true,
                'down_payment' => fake()->randomFloat(2, $price * 0.1, $price * 0.5),
            ];
        });
    }
}

