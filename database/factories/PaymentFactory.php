<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'customer_id' => fn (array $attributes) => Order::find($attributes['order_id'])?->customer_id,
            'user_id' => null,
            'amount' => fake()->numberBetween(1000, 20_000),
            'method' => fake()->randomElement(['cash', 'card', 'transfer']),
            'paid_at' => now(),
            'note' => null,
        ];
    }
}
