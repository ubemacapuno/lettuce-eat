<?php

namespace App\Models;

use Database\Factories\DishFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dish extends Model
{
    /** @use HasFactory<DishFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'rating',
        'notes',
        'order_again',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    protected function casts(): array
    {
        return [
            'rating' => 'decimal:1',
            'order_again' => 'boolean',
        ];
    }
}
