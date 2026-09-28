<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecipeIngredient extends Model {
    protected $table = 'RecipeIngredient';

    public $timestamps = false;

    protected $guarded = ['id'];

    public function recipe(): BelongsTo {
        return $this->belongsTo(Recipe::class, 'recipe_id');
    }

    public function ingredient(): BelongsTo {
        return $this->belongsTo(Ingredient::class, 'ingredient_id');
    }

    public function measure(): BelongsTo {
        return $this->belongsTo(Measure::class, 'measure_id');
    }
}
