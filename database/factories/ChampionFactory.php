<?php

namespace Database\Factories;

use App\Models\Champion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Champion>
 */
class ChampionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->firstName(),
            'role' => fake()->randomElement(['Rota Solo', 'Selva', 'Rota do Meio', 'Rota Dupla', 'Suporte']),
            'image_url' => fake()->imageUrl(100, 100, 'champions', true),
            'is_priority' => fake()->boolean(20),
        ];
    }
}
