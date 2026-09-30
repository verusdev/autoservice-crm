<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'Замена масла двигателя', 'Замена тормозных колодок', 'Компьютерная диагностика',
                'Замена свечей зажигания', 'Шиномонтаж', 'Замена амортизатора',
            ]).' #'.fake()->unique()->numberBetween(1, 9999),
            'category' => fake()->randomElement(['ТО', 'Ремонт', 'Диагностика', 'Шиномонтаж']),
            'norm_hours' => fake()->randomFloat(1, 0.5, 4.0),
            'price' => fake()->numberBetween(1500, 9000),
            'is_active' => true,
        ];
    }
}
