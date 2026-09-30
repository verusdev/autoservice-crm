<?php

namespace Database\Factories;

use App\Models\Part;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Part>
 */
class PartFactory extends Factory
{
    protected $model = Part::class;

    public function definition(): array
    {
        $price = fake()->numberBetween(200, 4000);

        return [
            'sku' => strtoupper(fake()->unique()->bothify('??-#####')),
            'name' => fake()->unique()->randomElement([
                'Масляный фильтр', 'Тормозные колодки', 'Моторное масло', 'Свеча зажигания',
                'Амортизатор', 'Ремень ГРМ', 'Помпа', 'Шаровая опора',
            ]).' '.fake()->unique()->numberBetween(100, 999),
            'brand' => fake()->randomElement(['Mann', 'TRW', 'NGK', 'KYB', 'Bosch']),
            'category' => 'Запчасти',
            'unit' => 'шт',
            'price' => $price,
            'sell_price' => (int) ($price * 1.4),
            'min_stock' => 3,
            'qty' => fake()->numberBetween(5, 40),
            'location' => strtoupper(fake()->bothify('?-##')),
            'is_active' => true,
        ];
    }

    public function outOfStock(): static
    {
        return $this->state(fn (): array => ['qty' => 0, 'min_stock' => 0]);
    }

    public function lowStock(): static
    {
        return $this->state(fn (): array => ['qty' => 1, 'min_stock' => 3]);
    }
}
