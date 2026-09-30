<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'brand' => fake()->randomElement(['Toyota', 'Lada', 'Kia', 'Hyundai', 'Volkswagen', 'BMW']),
            'model' => fake()->randomElement(['Camry', 'Vesta', 'Rio', 'Solaris', 'Polo', 'X5']),
            'year' => fake()->numberBetween(2008, (int) date('Y')),
            'vin' => strtoupper(fake()->bothify('??######??######???')),
            'plate' => strtoupper(fake()->bothify('?###??##')),
            'color' => fake()->randomElement(['белый', 'чёрный', 'серебристый', 'синий']),
            'engine' => fake()->randomFloat(1, 1.2, 4.0),
            'transmission' => fake()->randomElement(['Механика', 'Автомат']),
            'fuel' => 'Бензин',
            'mileage' => fake()->numberBetween(20_000, 300_000),
            'next_service_date' => null,
            'next_service_mileage' => null,
            'note' => null,
        ];
    }

    public function dueForService(): static
    {
        return $this->state(fn (): array => [
            'next_service_date' => now()->addDays(10),
            'next_service_mileage' => 200_000,
        ]);
    }
}
