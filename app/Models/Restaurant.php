<?php

namespace App\Models;

use Database\Factories\RestaurantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Restaurant extends Model
{
    /** @use HasFactory<RestaurantFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'rating',
        'notes',
        'street_address',
        'city',
        'state',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dishes(): HasMany
    {
        return $this->hasMany(Dish::class);
    }

    protected function casts(): array{
        return [
            'rating' => 'decimal:1',
        ];
    }
}
