<?php

namespace Database\Seeders;

use App\Models\Dish;
use App\Models\Recipe;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // seed a restaurant
        $portillos = Restaurant::factory()->for($user)->create([
            'name' => "Portillo's",
            'rating' => 4.5,
            'notes' => 'Fast line, always packed.',
            'street_address' => '1080 N McQueen Rd',
            'city' => 'Gilbert',
            'state' => 'AZ',
        ]);

        // seed some dishes for the restaurant
        Dish::factory()->for($portillos)->create([
            'name' => 'Italian Beef',
            'rating' => 4.5,
            'notes' => 'Ask for the gravy on the side, add hot peppers.',
            'order_again' => true,
        ]);
        Dish::factory()->for($portillos)->create([
            'name' => 'Cake Shake',
            'rating' => 4.0,
            'notes' => 'Order sparingly, very sweet!',
            'order_again' => false,
        ]);

        Restaurant::factory()
            ->count(5)
            ->for($user)
            ->has(Dish::factory()->count(3))
            ->create();

        Recipe::factory()
            ->count(5)
            ->for($user)
            ->create();
    }
}
