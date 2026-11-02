<?php

namespace Tests\Feature;

use App\Models\Chef;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ChefProfileTest extends TestCase {
    use RefreshDatabase;

    public function testProfileShowsWisdomOnItsOwnAndEscapesProfileText(): void {
        $chef = Chef::create(['name' => 'Family Chef', 'wisdom' => 'Taste as you go.']);

        $this->get('/chefs/' . $chef->id)->assertOk()
            ->assertSee('Words of Wisdom')->assertSee('Taste as you go.')
            ->assertSee('From Family Chef')->assertSee('Favorites')
            ->assertDontSee('Edit Chef Profile');

        $chef->update(['story' => "Cooking together.\n<script>alert(1)</script>", 'favorite_foods' => 'Soup & bread']);
        $this->get('/chefs/' . $chef->id)->assertOk()
            ->assertSee('Her Story')->assertSee('Cooking together.')
            ->assertSee('<script>alert(1)</script>')->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('Favorite Foods')->assertSee('Soup & bread')
            ->assertSee(route('recipes.index', ['chef_id' => $chef->id]));
    }

    public function testRecipeAndFavoriteSamplesContainTheSixMostLikedPublishedRecipes(): void {
        $chef = Chef::create(['name' => 'Family Chef']);
        $otherChef = Chef::create(['name' => 'Another Chef']);
        $owner = User::factory()->create(['chef_id' => $chef->id, 'is_chef' => true]);
        $voters = User::factory()->count(8)->create();
        $recipes = $favorites = [];

        for ($likes = 1; $likes <= 8; $likes++) {
            $recipe = Recipe::create(['title' => 'Recipe ' . $likes, 'instructions' => 'Cook.', 'chef_id' => $chef->id, 'published_flag' => true]);
            $favorite = Recipe::create(['title' => 'Favorite ' . $likes, 'instructions' => 'Cook.', 'chef_id' => $otherChef->id, 'published_flag' => true]);
            $recipe->favoritedBy()->attach($voters->take($likes)->modelKeys());
            $favorite->favoritedBy()->attach($voters->take($likes)->modelKeys());
            $owner->favoriteRecipes()->attach($favorite);
            $recipes[] = $recipe->id;
            $favorites[] = $favorite->id;
        }

        $private = Recipe::create(['title' => 'Private', 'instructions' => 'Secret.', 'chef_id' => $chef->id]);
        $private->favoritedBy()->attach($voters->modelKeys());
        $owner->favoriteRecipes()->attach($private);

        foreach (['recipes' => $recipes, 'favorites' => $favorites] as $collection => $ids) {
            $response = $this->getJson('/api/v1/chefs/' . $chef->id . '/' . $collection)->assertOk()->assertJsonCount(6, 'data');
            $this->assertSame(array_slice(array_reverse($ids), 0, 6), array_column($response->json('data'), 'id'));
            $response->assertJsonPath('data.0.is_liked', false);
        }

        $this->actingAs($owner)->getJson('/api/v1/chefs/' . $chef->id . '/favorites')->assertOk()
            ->assertJsonPath('data.0.is_liked', true)->assertJsonPath('data.0.likes_count', 9);
        $this->get('/chefs/' . $chef->id)->assertOk()->assertSee('Edit Chef Profile');
    }

    public function testChefWithoutAnAccountOrRecipesHasEmptySamples(): void {
        $chef = Chef::create(['name' => 'New Chef']);

        foreach (['recipes', 'favorites'] as $collection) {
            $this->getJson('/api/v1/chefs/' . $chef->id . '/' . $collection)->assertOk()->assertExactJson(['data' => []]);
            $this->getJson('/api/v1/chefs/99999/' . $collection)->assertNotFound();
        }
    }
}
