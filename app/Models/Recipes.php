<?php

namespace App\Models;

use Database\Factories\RecipesFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recipes extends Model
{
    /** @use HasFactory<RecipesFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'rating',
        'ingredients',
        'instructions',
        'source_url',
        'total_minutes',
        'servings',
        'make_again',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'rating' => 'decimal:1',
        ];
    }
}
