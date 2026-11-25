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
        $floor = fake()->numberBetween(1, 9);
        $unit = fake()->unique()->numberBetween(1, 99);

        return [
            'number' => $floor.str_pad((string) $unit, 2, '0', STR_PAD_LEFT),
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
