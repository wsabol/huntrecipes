<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SetDailyFeatures extends Command {
    protected $signature = 'huntrecipes:set-daily-features';

    protected $description = 'Select the daily recipe and chef.';

    public function handle(): int {
        DB::transaction(function () {
            $day = now()->toDateString();
            $recipe = DB::table('Recipe')->where('published_flag', 1)->whereNotIn('id', DB::table('RecipeOfTheDay')->where('day', '>=', now()->subDays(3)->toDateString())->pluck('recipe_id'))->inRandomOrder()->value('id');
            $chef = DB::table('Chef')->whereNotIn('id', DB::table('ChefOfTheDay')->where('day', '>=', now()->subDays(3)->toDateString())->pluck('chef_id'))->inRandomOrder()->value('id');
            if ($recipe) {
                DB::table('RecipeOfTheDay')->updateOrInsert(['day' => $day], ['recipe_id' => $recipe]);
            }
            if ($chef) {
                DB::table('ChefOfTheDay')->updateOrInsert(['day' => $day], ['chef_id' => $chef]);
            }
            DB::table('RecipeOfTheDay')->where('day', '<', now()->subDays(30)->toDateString())->delete();
            DB::table('ChefOfTheDay')->where('day', '<', now()->subDays(30)->toDateString())->delete();
        });
        $this->info('Daily recipe and chef selected.');

        return self::SUCCESS;
    }
}
