<?php

use App\Models\Recipe;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void {
        Schema::table('Recipe', fn (Blueprint $table) => $table->string('slug')->nullable()->unique());

        foreach (DB::table('Recipe')->orderBy('id')->lazyById() as $recipe) {
            DB::table('Recipe')->where('id', $recipe->id)->update(['slug' => Recipe::slugForTitle($recipe->title)]);
        }

        Schema::table('Recipe', fn (Blueprint $table) => $table->string('slug')->nullable(false)->change());
    }

    public function down(): void {
        Schema::table('Recipe', function (Blueprint $table): void {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
