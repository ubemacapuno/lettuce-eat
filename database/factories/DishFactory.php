<?php

namespace Database\Factories;

use App\Models\Dish;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dish>
 */
class DishFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'restaurant_id' => Restaurant::factory(),
            'name' => fake()->randomElement([
                'Italian Beef',
                'Bánh canh cua',
                'Cake Shake',
                'Carne Asada Tacos',
                'Green Chile Burrito',
                'Margherita Pizza',
                'Pad Thai',
                'Phở Đặc Biệt',
                'Fried Chicken Sandwich',
                'Pork Belly Ramen',
            ]),
        ];
    }

    public function reviewed(): static
    {
        return $this->state(fn (): array => [
            'rating' => fake()->boolean(85) ? fake()->numberBetween(2, 10) / 2 : null,
            'notes' => fake()->boolean(60) ? fake()->sentence() : null,
            'order_again' => fake()->boolean(55),
        ]);
    }
}
