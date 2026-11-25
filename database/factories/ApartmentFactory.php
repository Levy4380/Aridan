<?php

namespace Database\Factories;

use App\Models\Apartment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Apartment>
 */
class ApartmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // 4+ digits: never clashes with the real 3-digit numbers tests use ("105").
            'number' => (string) fake()->unique()->numberBetween(1000, 999999),
            'capacity' => fake()->numberBetween(2, 5),
            'is_active' => true,
            'description' => null,
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
