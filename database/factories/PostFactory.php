<?php

namespace Database\Factories;

use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        $name = 'Слесарный пост №'.fake()->unique()->numberBetween(1, 999);

        return [
            'name' => $name,
            'code' => Str::upper(Str::random(3)),
            'capacity_hours' => 8,
            'hourly_rate' => fake()->numberBetween(800, 1800),
            'description' => fake()->sentence(3),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
