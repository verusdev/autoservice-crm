<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'name' => fake()->lastName().' '.fake()->firstName().' '.mb_strtoupper(mb_substr(fake()->firstName(), 0, 1)).'.',
            'phone' => fake()->unique()->numerify('+79#########'),
            'phone2' => null,
            'email' => fake()->unique()->safeEmail(),
            'type' => 'person',
            'company' => null,
            'source' => fake()->randomElement(['2ГИС', 'Сайт', 'Рекомендация', 'Карты']),
            'address' => fake()->streetAddress(),
            'birth_date' => null,
            'discount' => 0,
            'notes' => null,
        ];
    }

    public function company(): static
    {
        return $this->state(fn (): array => [
            'type' => 'company',
            'name' => 'Представитель компании',
            'company' => fake()->company(),
        ]);
    }

    public function withDiscount(int $percent = 10): static
    {
        return $this->state(fn (): array => ['discount' => $percent]);
    }
}
