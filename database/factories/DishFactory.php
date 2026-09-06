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
}
