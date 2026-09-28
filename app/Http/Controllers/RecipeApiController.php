<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveRecipeRequest;
use App\Http\Resources\RecipeResource;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Services\RecipeImageService;
use App\Services\RecipeQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RecipeApiController extends Controller {
    public function index(Request $request, RecipeQueryService $recipes): AnonymousResourceCollection {
        return RecipeResource::collection($recipes->search($request->only(['keyword', 'recipe_type_id', 'course_id', 'cuisine_id', 'chef_id', 'ingredients', 'per_page']), $request->user()));
    }

    public function show(Recipe $recipe): RecipeResource {
        $this->authorize('view', $recipe);

        return new RecipeResource($recipe->load(['chef', 'course', 'cuisine', 'type', 'ingredients.ingredient', 'ingredients.measure'])->loadCount('favoritedBy'));
    }

    public function store(SaveRecipeRequest $request): JsonResponse {
        $this->authorize('create', Recipe::class);
        $recipe = DB::transaction(function () use ($request) {
            $recipe = new Recipe($this->recipeData($request));
            $recipe->chef_id = $request->user()->chef_id;
            $recipe->save();
            $this->syncIngredients($recipe, $request->validated('ingredients', []));

            return $recipe;
        });

        return (new RecipeResource($recipe->load(['ingredients.ingredient', 'ingredients.measure'])))
            ->response()
            ->setStatusCode(201);
    }

    public function update(SaveRecipeRequest $request, Recipe $recipe): RecipeResource {
        $this->authorize('update', $recipe);
        $oldImage = $recipe->image_filename;
        DB::transaction(function () use ($request, $recipe) {
            $recipe->update($this->recipeData($request));
            $this->syncIngredients($recipe, $request->validated('ingredients', []));
        });
        if ($request->hasFile('recipe_image')) {
            $this->deleteManagedImage($oldImage);
        }

        return new RecipeResource($recipe->refresh()->load(['ingredients.ingredient', 'ingredients.measure']));
    }

    public function destroy(Recipe $recipe): Response {
        $this->authorize('delete', $recipe);
        DB::transaction(function () use ($recipe): void {
            $recipe->ingredients()->delete();
            $recipe->favoritedBy()->detach();
            $recipe->delete();
        });
        $this->deleteManagedImage($recipe->image_filename);

        return response()->noContent();
    }

    public function favorite(Request $request, Recipe $recipe): RecipeResource {
        $this->authorize('view', $recipe);
        $request->user()->favoriteRecipes()->syncWithoutDetaching([$recipe->id]);

        return new RecipeResource($recipe->loadCount('favoritedBy')->setAttribute('is_liked', true));
    }

    public function unfavorite(Request $request, Recipe $recipe): RecipeResource {
        $this->authorize('view', $recipe);
        $request->user()->favoriteRecipes()->detach($recipe->id);

        return new RecipeResource($recipe->loadCount('favoritedBy')->setAttribute('is_liked', false));
    }

    public function photo(Request $request, Recipe $recipe): RecipeResource {
        $this->authorize('update', $recipe);
        $request->validate(['recipe_photo' => ['required', 'image', 'max:8192']]);
        $oldImage = $recipe->image_filename;
        $path = $request->file('recipe_photo')->store('recipes', 'public');
        $recipe->update(['image_filename' => '/storage/' . $path]);
        $this->deleteManagedImage($oldImage);

        return new RecipeResource($recipe->refresh());
    }

    public function destroyPhoto(Recipe $recipe): RecipeResource {
        $this->authorize('update', $recipe);
        $this->deleteManagedImage($recipe->image_filename);
        $recipe->update(['image_filename' => '/assets/images/recipes/generic_recipe.jpg']);

        return new RecipeResource($recipe->refresh());
    }

    public function generatedPhoto(Request $request, Recipe $recipe, RecipeImageService $images) {
        $this->authorize('generateImage', $recipe);
        $data = $request->validate(['image_prompt' => ['nullable', 'string', 'max:4000']]);
        $generated = $images->generate($recipe, $data['image_prompt'] ?? null);
        $this->deleteManagedImage($recipe->image_filename);
        $recipe->update(['image_filename' => $generated['generated_image']]);

        return response()->json(['data' => $generated + ['recipe' => new RecipeResource($recipe->refresh())]]);
    }

    private function recipeData(SaveRecipeRequest $request): array {
        $data = $request->safe()->except(['ingredients', 'recipe_image']);
        if ($request->hasFile('recipe_image')) {
            $data['image_filename'] = '/storage/' . $request->file('recipe_image')->store('recipes', 'public');
        }

        return $data;
    }

    private function syncIngredients(Recipe $recipe, array $rows): void {
        $recipe->ingredients()->delete();
        foreach ($rows as $row) {
            $ingredient = Ingredient::firstOrCreate(['name' => strtolower(trim($row['raw_ingredient_name']))], ['name_plural' => strtolower(trim($row['raw_ingredient_name'])) . 's']);
            $recipe->ingredients()->create(['ingredient_id' => $ingredient->id, 'ingredient_prep' => strtolower(trim($row['ingredient_prep'] ?? '')), 'measure_id' => (int)$row['measure_id'], 'amount' => $this->decimalAmount($row['amount'] ?? 0), 'optional_flag' => (bool)($row['optional'] ?? false)]);
        }
    }

    private function decimalAmount(mixed $amount): float {
        if (is_numeric($amount)) {
            return (float)$amount;
        }
        $total = 0.0;
        foreach (preg_split('/\s+/', trim((string)$amount)) as $part) {
            if (str_contains($part, '/')) {
                [$n, $d] = array_map('floatval', explode('/', $part, 2));
                if ($d) {
                    $total += $n / $d;
                }
            } elseif (is_numeric($part)) {
                $total += (float)$part;
            }
        }

        return $total;
    }

    private function deleteManagedImage(?string $url): void {
        if (str_starts_with((string)$url, '/storage/recipes/')) {
            Storage::disk('public')->delete(substr((string)$url, strlen('/storage/')));
        }
    }
}
