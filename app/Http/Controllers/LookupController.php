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
        return response()->json(['data' => Chef::withExists('user')->orderBy('name')->get()->map(fn ($chef) => [...$chef->toArray(), 'is_linked_to_user' => $chef->user_exists])]);
    }

    public function chefRecipes(Request $request, Chef $chef) {
        return RecipeResource::collection($chef->recipes()->where('published_flag', true)
            ->with(['chef', 'course', 'cuisine', 'type'])->withCount('favoritedBy')
            ->withExists(['favoritedBy as is_liked' => fn ($q) => $q->where('User.id', $request->user()?->id ?? 0)])
            ->orderByDesc('favorited_by_count')->orderBy('Recipe.id')->limit(6)->get());
    }

    public function chefFavorites(Request $request, Chef $chef) {
        $user = $chef->user;
        if (!$user) {
            return RecipeResource::collection(collect());
        }

        return RecipeResource::collection($user->favoriteRecipes()->where('published_flag', true)
            ->with(['chef', 'course', 'cuisine', 'type'])->withCount('favoritedBy')
            ->withExists(['favoritedBy as is_liked' => fn ($q) => $q->where('User.id', $request->user()?->id ?? 0)])
            ->orderByDesc('favorited_by_count')->orderBy('Recipe.id')->limit(6)->get());
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
        return response()->json(['data' => RecipeIngredient::whereNotNull('ingredient_prep')->where('ingredient_prep', '<>', '')->distinct()->orderBy('ingredient_prep')->pluck('ingredient_prep')->map(fn ($name) => ['name' => $name])]);
    }

    public function measures() {
        return response()->json(['data' => Measure::query()->get()->map(fn ($measure) => [...$measure->toArray(), 'measure_type' => $measure->measure_type_id, 'base_unit_conversion' => $measure->general_unit_conversion, 'fractions_allowed' => $measure->fractionsAllowed(), 'is_metric' => in_array((int) $measure->id, config('measures.metric_ids'), true)])]);
    }

    private function collection(string $model) {
        /** @var Model $model */
        return response()->json(['data' => $model::orderBy('name')->get()]);
    }
}
