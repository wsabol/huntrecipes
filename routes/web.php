<?php

use App\Http\Controllers\AuthSessionController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

Route::middleware('firebase')->group(function (): void {
    Route::get('/', [PageController::class, 'root'])->name('root');
    Route::get('/welcome', [PageController::class, 'welcome'])->name('welcome');
    Route::get('/home', [PageController::class, 'home'])->name('home');
    Route::get('/about', [PageController::class, 'about'])->name('about');
    Route::get('/contact', [PageController::class, 'contact'])->name('contact');
    Route::get('/privacy', [PageController::class, 'privacy'])->name('privacy');
    Route::get('/recipes', [PageController::class, 'recipes'])->name('recipes.index');
    Route::get('/recipes/{legacyRecipe}', [PageController::class, 'legacyRecipe'])->whereNumber('legacyRecipe');
    Route::get('/recipes/{recipe:slug}', [PageController::class, 'recipe'])->where('recipe', '(?!create$)[a-z0-9-]+')->name('recipes.show');
    Route::get('/recipes/{recipe}/print', [PageController::class, 'printRecipe'])->whereNumber('recipe')->name('recipes.print');
    Route::get('/chefs/{chef}', [PageController::class, 'chef'])->whereNumber('chef')->name('chefs.show');
    Route::get('/sign-in', [PageController::class, 'signIn'])->name('sign-in');
    Route::get('/auth/email/finish', [PageController::class, 'finishEmailSignIn'])->name('auth.email.finish');

    Route::post('/api/v1/auth/session', [AuthSessionController::class, 'store'])->middleware('throttle:10,1')->name('auth.session.store');
    Route::delete('/api/v1/auth/session', [AuthSessionController::class, 'destroy'])->name('auth.session.destroy');
});

Route::middleware('firebase:required')->group(function (): void {
    Route::get('/account', [PageController::class, 'account'])->name('account');
    Route::get('/recipes/create', [PageController::class, 'recipeForm'])->name('recipes.create');
    Route::get('/recipes/{recipe}/edit', [PageController::class, 'recipeForm'])->whereNumber('recipe')->name('recipes.edit');
    Route::get('/admin', [PageController::class, 'admin'])->name('admin.index');
    Route::get('/admin/chef-applications', [PageController::class, 'chefApplications'])->name('admin.chef-applications');
});
