<?php

namespace App\Http\Controllers;

use App\Http\Resources\RecipeResource;
use App\Models\Chef;
use App\Models\Recipe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeaturedController extends Controller {
    public function recipeOfDay() {
        $id = DB::table('RecipeOfTheDay')->orderByDesc('day')->value('recipe_id');
        abort_unless($id, 404);

        return new RecipeResource(Recipe::with(['chef', 'course', 'cuisine', 'type'])->withCount('favoritedBy')->where('published_flag', true)->findOrFail($id));
    }

    public function chefOfDay() {
        $id = DB::table('ChefOfTheDay')->orderByDesc('day')->value('chef_id');
        abort_unless($id, 404);

        return response()->json(['data' => Chef::findOrFail($id)]);
    }

    public function top(Request $request) {
        return RecipeResource::collection(Recipe::with(['chef', 'course', 'cuisine', 'type'])->withCount('favoritedBy')
            ->withExists(['favoritedBy as is_liked' => fn ($q) => $q->where('User.id', $request->user()?->id ?? 0)])
            ->where('published_flag', true)->orderByDesc('favorited_by_count')->limit(6)->get());
    }
}
