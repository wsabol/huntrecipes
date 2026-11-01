<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void {
        if (!Schema::hasTable('Chef')) {
            Schema::create('Chef', function (Blueprint $t) {
                $t->id();
                $t->string('name');
                $t->boolean('is_male')->default(false);
                $t->string('wisdom')->default('');
                $t->text('story')->nullable();
                $t->string('favorite_foods')->default('');
            });
        }
        if (!Schema::hasTable('User')) {
            Schema::create('User', function (Blueprint $t) {
                $t->id();
                $t->string('firebase_uid')->nullable()->unique();
                $t->string('name');
                $t->string('email')->unique();
                $t->string('password')->nullable();
                $t->unsignedInteger('account_status_id')->default(1);
                $t->string('profile_picture')->default('/assets/images/users/generic_avatar.jpg');
                $t->unsignedBigInteger('chef_application_id')->default(0);
                $t->unsignedBigInteger('chef_id')->default(0);
                $t->boolean('is_chef')->default(false);
                $t->boolean('is_developer')->default(false);
                $t->boolean('is_email_verified')->default(false);
                $t->dateTime('date_created')->useCurrent();
            });
        } elseif (!Schema::hasColumn('User', 'firebase_uid')) {
            Schema::table('User', fn (Blueprint $t) => $t->string('firebase_uid', 128)->nullable()->unique()->after('id'));
        }
        if (Schema::hasColumn('User', 'password')) {
            Schema::table('User', fn (Blueprint $t) => $t->string('password')->nullable()->change());
        }
        foreach (['Course', 'Cuisine', 'RecipeType'] as $table) {
            if (!Schema::hasTable($table)) {
                Schema::create($table, function (Blueprint $t) {
                    $t->id();
                    $t->string('name');
                    $t->string('icon')->default('');
                });
            }
        }
        if (!Schema::hasTable('MeasureType')) {
            Schema::create('MeasureType', function (Blueprint $t) {
                $t->id();
                $t->string('name');
                $t->unsignedBigInteger('general_measure_id')->default(0);
            });
        }
        if (!Schema::hasTable('Measure')) {
            Schema::create('Measure', function (Blueprint $t) {
                $t->id();
                $t->string('name');
                $t->string('name_plural');
                $t->string('abbr')->default('');
                $t->unsignedBigInteger('measure_type_id')->default(0);
                $t->decimal('general_unit_conversion', 12, 6)->default(1);
            });
        }
        if (!Schema::hasTable('Ingredient')) {
            Schema::create('Ingredient', function (Blueprint $t) {
                $t->id();
                $t->string('name');
                $t->string('name_plural')->default('');
            });
        }
        if (!Schema::hasTable('Recipe')) {
            Schema::create('Recipe', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('course_id')->default(0);
                $t->unsignedBigInteger('cuisine_id')->default(0);
                $t->unsignedBigInteger('type_id')->default(0);
                $t->unsignedBigInteger('chef_id')->default(0);
                $t->string('title');
                $t->longText('instructions');
                $t->string('image_filename')->default('/assets/images/recipes/generic_recipe.jpg');
                $t->decimal('serving_count', 10, 2)->default(0);
                $t->unsignedBigInteger('serving_measure_id')->default(0);
                $t->unsignedBigInteger('parent_recipe_id')->default(0);
                $t->boolean('published_flag')->default(false);
            });
        }
        if (!Schema::hasTable('RecipeIngredient')) {
            Schema::create('RecipeIngredient', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('recipe_id')->index();
                $t->unsignedBigInteger('ingredient_id')->index();
                $t->string('ingredient_prep')->default('');
                $t->unsignedBigInteger('measure_id')->default(0);
                $t->decimal('amount', 10, 3)->default(0);
                $t->boolean('optional_flag')->default(false);
                $t->unique(['recipe_id', 'ingredient_id']);
            });
        }
        if (!Schema::hasTable('UserRecipeFavorite')) {
            Schema::create('UserRecipeFavorite', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('recipe_id')->index();
                $t->unsignedBigInteger('user_id')->index();
                $t->unique(['recipe_id', 'user_id']);
            });
        }
        if (!Schema::hasTable('ChefApplication')) {
            Schema::create('ChefApplication', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('user_id')->index();
                $t->unsignedInteger('chef_application_status_id')->default(1);
                $t->dateTime('date_created')->useCurrent();
                $t->boolean('already_exists')->default(false);
                $t->string('relationship')->default('');
                $t->text('story')->nullable();
                $t->boolean('is_deleted')->default(false);
            });
        }
        if (!Schema::hasTable('RecipeOfTheDay')) {
            Schema::create('RecipeOfTheDay', function (Blueprint $t) {
                $t->date('day')->primary();
                $t->unsignedBigInteger('recipe_id');
            });
        }
        if (!Schema::hasTable('ChefOfTheDay')) {
            Schema::create('ChefOfTheDay', function (Blueprint $t) {
                $t->date('day')->primary();
                $t->unsignedBigInteger('chef_id');
            });
        }
    }

    public function down(): void {
        // This migration adopts a pre-existing production schema and is intentionally non-destructive.
    }
};
