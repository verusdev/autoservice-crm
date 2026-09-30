<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Service;
use App\Models\Work;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Work>
 */
class WorkFactory extends Factory
{
    protected $model = Work::class;

    public function definition(): array
    {
        $hours = fake()->randomFloat(2, 0.5, 4.0);
        $rate = fake()->numberBetween(900, 1500);

        return [
            'order_id' => Order::factory(),
            'service_id' => Service::factory(),
            'name' => fake()->randomElement([
                'Замена масла двигателя', 'Замена тормозных колодок', 'Диагностика подвески',
            ]),
            'norm_hours' => $hours,
            'hourly_rate' => $rate,
            'price' => $hours * $rate,
            'mechanic_id' => null,
            'status' => 'planned',
            'completed_at' => null,
            'comment' => null,
        ];
    }

    public function done(): static
    {
        return $this->state(fn (): array => [
            'status' => 'done',
            'completed_at' => now(),
        ]);
    }
}
