<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Post;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'number' => 'ЗН-'.fake()->unique()->numerify('2026-####'),
            'customer_id' => Customer::factory(),
            'vehicle_id' => Vehicle::factory(),
            'post_id' => Post::factory(),
            'mechanic_id' => null,
            'advisor_id' => null,
            'status' => 'new',
            'mileage' => fake()->numberBetween(20_000, 300_000),
            'intake_at' => now()->subDays(fake()->numberBetween(0, 10)),
            'promised_at' => now()->addDay(),
            'closed_at' => null,
            'complaint' => fake()->sentence(4),
            'note' => null,
            'discount' => 0,
            'total_work' => 0,
            'total_parts' => 0,
            'total' => 0,
            'paid' => 0,
            'is_invoice' => false,
        ];
    }

    public function issued(): static
    {
        return $this->state(fn (): array => [
            'status' => 'issued',
            'closed_at' => now()->subHours(3),
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (): array => ['status' => 'in_progress']);
    }

    public function forMechanic(User $mechanic): static
    {
        return $this->state(fn (): array => ['mechanic_id' => $mechanic->id]);
    }
}
