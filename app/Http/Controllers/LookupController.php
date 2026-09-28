<?php

namespace App\Http\Controllers;

use App\Http\Resources\RecipeResource;
use App\Models\Chef;
use App\Models\Course;
use App\Models\Cuisine;
use App\Models\Ingredient;
use App\Models\Measure;
use App\Models\RecipeIngredient;
use App\Models\RecipeType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class LookupController extends Controller {
    public function chefs() {
        return response()->json(['data' => Chef::withExists('user')->orderBy('name')->get()->map(fn($chef) => [...$chef->toArray(), 'is_linked_to_user' => $chef->user_exists])]);
    }

    public function chefFavorites(Chef $chef) {
        $user = $chef->user;
        if (!$user) {
            return RecipeResource::collection(collect());
        }

        return RecipeResource::collection($user->favoriteRecipes()->where('published_flag', true)->with(['chef', 'course', 'cuisine', 'type'])->withCount('favoritedBy')->get());
    }

    public function users(Request $request) {
        $this->authorize('viewAny', User::class);

        return response()->json(['data' => User::select(['id', 'name', 'email'])->orderBy('name')->get()]);
    }

    public function courses() {
        return $this->collection(Course::class);
    }

    public function cuisines() {
        return $this->collection(Cuisine::class);
    }

    public function recipeTypes() {
        return $this->collection(RecipeType::class);
    }

    public function ingredients() {
        return response()->json(['data' => Ingredient::orderBy('name')->get()]);
    }

    public function ingredientPreparations() {
        return response()->json(['data' => RecipeIngredient::whereNotNull('ingredient_prep')->where('ingredient_prep', '<>', '')->distinct()->orderBy('ingredient_prep')->pluck('ingredient_prep')->map(fn($name) => ['name' => $name])]);
    }

    public function measures() {
        return response()->json(['data' => Measure::query()->get()->map(fn($measure) => [...$measure->toArray(), 'measure_type' => $measure->measure_type_id, 'base_unit_conversion' => $measure->general_unit_conversion, 'fractions_allowed' => []])]);
    }

    private function collection(string $model) {
        /** @var Model $model */
        return response()->json(['data' => $model::orderBy('name')->get()]);
    }
}
