<?php

namespace Tests\Feature;

use App\Models\ChefApplication;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class OwnershipBoundaryTest extends TestCase {
    use RefreshDatabase;

    public function testProfileUpdateIgnoresSubmittedOwnershipFields(): void {
        $actor = User::factory()->create(['name' => 'Actor']);
        $victim = User::factory()->create(['name' => 'Victim']);

        $this->actingAs($actor)->patchJson('/api/v1/me', [
            'user_id' => $victim->id,
            'name' => 'Updated Actor',
        ])->assertOk();

        $this->assertSame('Updated Actor', $actor->refresh()->name);
        $this->assertSame('Victim', $victim->refresh()->name);
    }

    public function testFavoriteIsAlwaysWrittenForTheActingUser(): void {
        $actor = User::factory()->create();
        $victim = User::factory()->create();
        $recipe = Recipe::create(['title' => 'Published', 'instructions' => 'Cook it', 'published_flag' => true]);

        $this->actingAs($actor)->postJson("/api/v1/recipes/{$recipe->id}/favorite", [
            'user_id' => $victim->id,
        ])->assertOk();

        $this->assertTrue($actor->favoriteRecipes()->whereKey($recipe->id)->exists());
        $this->assertFalse($victim->favoriteRecipes()->whereKey($recipe->id)->exists());
    }

    public function testUserCannotChangeAnotherChefsRecipeOrApplication(): void {
        $actor = User::factory()->create(['is_chef' => true, 'chef_id' => 8]);
        $victim = User::factory()->create(['is_chef' => true, 'chef_id' => 7]);
        $recipe = Recipe::create(['title' => 'Private', 'instructions' => 'Secret', 'chef_id' => 7, 'published_flag' => false]);
        $application = ChefApplication::create(['user_id' => $victim->id, 'relationship' => 'family', 'story' => 'Story']);

        $this->actingAs($actor)->patchJson("/api/v1/recipes/{$recipe->id}", [
            'title' => 'Stolen',
            'instructions' => 'Changed',
            'ingredients' => [],
        ])->assertForbidden();
        $this->actingAs($actor)->deleteJson("/api/v1/chef-applications/{$application->id}")->assertForbidden();

        $this->assertSame('Private', $recipe->refresh()->title);
        $this->assertFalse($application->refresh()->is_deleted);
    }
}
