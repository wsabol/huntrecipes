<?php

namespace Tests\Feature;

use App\Models\Recipe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class RecipeSlugTest extends TestCase {
    use RefreshDatabase;

    public function testRecipeUsesSlugAndOldNumericUrlRedirects(): void {
        $recipe = Recipe::create(['title' => 'Almond Butter Spritz Cookies', 'instructions' => 'Bake.', 'published_flag' => true]);
        $url = route('recipes.show', $recipe->slug);

        $this->assertSame('almond-butter-spritz-cookies', $recipe->slug);
        $this->get($url)->assertOk()->assertSee('Almond Butter Spritz Cookies');
        $this->get('/recipes/' . $recipe->id)->assertStatus(301)->assertRedirect($url);
        $this->getJson('/api/v1/recipes/' . $recipe->id)->assertOk()
            ->assertJsonPath('data.slug', $recipe->slug)->assertJsonPath('data.link', $url);
        $this->get('/recipes/missing-recipe')->assertNotFound();
    }

    public function testSlugsAreUniqueAndRemainStableAfterTitleChanges(): void {
        $recipe = Recipe::create(['title' => 'Cookies', 'instructions' => 'Bake.']);
        $duplicate = Recipe::create(['title' => 'Cookies', 'instructions' => 'Bake.']);
        $collision = Recipe::create(['title' => 'Cookies 2', 'instructions' => 'Bake.']);

        $this->assertSame('cookies', $recipe->slug);
        $this->assertSame('cookies-2', $duplicate->slug);
        $this->assertSame('cookies-2-2', $collision->slug);
        $recipe->update(['title' => 'New Title']);
        $this->assertSame('cookies', $recipe->refresh()->slug);
    }

    public function testSpecialTitlesDoNotConflictWithNumericOrCreateRoutes(): void {
        foreach (['123' => 'recipe-123', 'Create' => 'recipe-create', '!!!' => 'recipe'] as $title => $slug) {
            $recipe = Recipe::create(['title' => (string)$title, 'instructions' => 'Bake.', 'published_flag' => true]);
            $this->assertSame($slug, $recipe->slug);
            $this->get('/recipes/' . $slug)->assertOk();
        }

        $this->get('/recipes/create')->assertRedirect(route('sign-in', ['return' => '/recipes/create']));
    }

    public function testPrivateRecipesRemainProtectedAtBothUrls(): void {
        $recipe = Recipe::create(['title' => 'Private Recipe', 'instructions' => 'Secret.']);

        $this->get('/recipes/' . $recipe->slug)->assertForbidden();
        $this->get('/recipes/' . $recipe->id)->assertForbidden();
    }

    public function testMigrationBackfillsExistingRecipesWithUniqueSlugs(): void {
        $migration = require database_path('migrations/2026_11_02_000000_add_recipe_slug.php');
        $migration->down();
        DB::table('Recipe')->insert([
            ['title' => 'Almond Butter Spritz Cookies', 'instructions' => 'Bake.'],
            ['title' => 'Almond Butter Spritz Cookies', 'instructions' => 'Bake again.'],
        ]);

        $migration->up();

        $this->assertSame(['almond-butter-spritz-cookies', 'almond-butter-spritz-cookies-2'], Recipe::orderBy('id')->pluck('slug')->all());
    }
}
