<?php

use App\Http\Controllers\ApiStatusController;
use App\Http\Controllers\AuthProviderController;
use App\Http\Controllers\ChefApplicationController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\FeaturedController;
use App\Http\Controllers\LookupController;
use App\Http\Controllers\ProfileApiController;
use App\Http\Controllers\RecipeApiController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::get('/status', ApiStatusController::class);

    Route::prefix('v1')->middleware('firebase')->group(function (): void {
        Route::get('/recipes', [RecipeApiController::class, 'index']);
        Route::get('/recipes/{recipe}', [RecipeApiController::class, 'show'])->whereNumber('recipe');
        Route::get('/chefs', [LookupController::class, 'chefs']);
        Route::get('/chefs/{chef}/favorites', [LookupController::class, 'chefFavorites'])->whereNumber('chef');
        Route::get('/chefs/{chef}/recipes', [LookupController::class, 'chefRecipes'])->whereNumber('chef');
        Route::get('/courses', [LookupController::class, 'courses']);
        Route::get('/cuisines', [LookupController::class, 'cuisines']);
        Route::get('/recipe-types', [LookupController::class, 'recipeTypes']);
        Route::get('/ingredients', [LookupController::class, 'ingredients']);
        Route::get('/ingredient-preparations', [LookupController::class, 'ingredientPreparations']);
        Route::get('/measures', [LookupController::class, 'measures']);
        Route::get('/featured/recipe', [FeaturedController::class, 'recipeOfDay']);
        Route::get('/featured/chef', [FeaturedController::class, 'chefOfDay']);
        Route::get('/featured/top-recipes', [FeaturedController::class, 'top']);
        Route::post('/contact', ContactController::class)->middleware('throttle:5,10');
    });

    Route::prefix('v1')->middleware(['firebase:required', 'throttle:api'])->group(function (): void {
        Route::get('/me', [ProfileApiController::class, 'show']);
        Route::get('/me/providers', [AuthProviderController::class, 'index']);
        Route::patch('/me', [ProfileApiController::class, 'update']);
        Route::post('/me/photo', [ProfileApiController::class, 'photo']);
        Route::get('/me/favorites', [ProfileApiController::class, 'favorites']);
        Route::get('/me/recipes', [ProfileApiController::class, 'recipes']);
        Route::patch('/me/chef', [ProfileApiController::class, 'chef']);
        Route::post('/recipes', [RecipeApiController::class, 'store']);
        Route::patch('/recipes/{recipe}', [RecipeApiController::class, 'update'])->whereNumber('recipe');
        Route::delete('/recipes/{recipe}', [RecipeApiController::class, 'destroy'])->whereNumber('recipe');
        Route::post('/recipes/{recipe}/favorite', [RecipeApiController::class, 'favorite'])->whereNumber('recipe');
        Route::delete('/recipes/{recipe}/favorite', [RecipeApiController::class, 'unfavorite'])->whereNumber('recipe');
        Route::post('/recipes/{recipe}/photo', [RecipeApiController::class, 'photo'])->whereNumber('recipe');
        Route::delete('/recipes/{recipe}/photo', [RecipeApiController::class, 'destroyPhoto'])->whereNumber('recipe');
        Route::post('/recipes/{recipe}/generated-photo', [RecipeApiController::class, 'generatedPhoto'])->whereNumber('recipe');
        Route::get('/chef-applications', [ChefApplicationController::class, 'index']);
        Route::patch('/chef-applications/{chefApplication}', [ChefApplicationController::class, 'review'])->whereNumber('chefApplication');
        Route::post('/chef-applications', [ChefApplicationController::class, 'store']);
        Route::delete('/chef-applications/{chefApplication}', [ChefApplicationController::class, 'destroy'])->whereNumber('chefApplication');
        Route::get('/users', [LookupController::class, 'users']);
        Route::delete('/me/providers/google', [AuthProviderController::class, 'destroyGoogle']);
    });
});
