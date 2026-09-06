<?php

namespace App\Policies;

use App\Models\Dish;
use App\Models\User;

class DishPolicy
{
    public function update(User $user, Dish $dish): bool
    {
        return $user->id === $dish->restaurant->user_id;
    }

    public function delete(User $user, Dish $dish): bool
    {
        return $user->id === $dish->restaurant->user_id;
    }
}
