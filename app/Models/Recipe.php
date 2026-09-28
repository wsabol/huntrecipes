<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recipe extends Model {
    protected $table = 'Recipe';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array {
        return ['published_flag' => 'boolean', 'serving_count' => 'float'];
    }

    public function chef(): BelongsTo {
        return $this->belongsTo(Chef::class, 'chef_id');
    }

    public function course(): BelongsTo {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function cuisine(): BelongsTo {
        return $this->belongsTo(Cuisine::class, 'cuisine_id');
    }

    public function type(): BelongsTo {
        return $this->belongsTo(RecipeType::class, 'type_id');
    }

    public function servingMeasure(): BelongsTo {
        return $this->belongsTo(Measure::class, 'serving_measure_id');
    }

    public function ingredients(): HasMany {
        return $this->hasMany(RecipeIngredient::class, 'recipe_id');
    }

    public function children(): HasMany {
        return $this->hasMany(self::class, 'parent_recipe_id');
    }

    public function favoritedBy(): BelongsToMany {
        return $this->belongsToMany(User::class, 'UserRecipeFavorite', 'recipe_id', 'user_id');
    }
}
