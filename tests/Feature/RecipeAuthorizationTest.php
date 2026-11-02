<?php

namespace Tests\Feature;

use App\Models\Recipe;
use App\Models\User;
use App\Policies\RecipePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class RecipeAuthorizationTest extends TestCase {
    use RefreshDatabase;

    public function testRecipeChangesAreLimitedToOwnerOrDeveloper(): void {
        $recipe = new Recipe(['chef_id' => 7, 'published_flag' => false]);
        $owner = User::factory()->make(['is_chef' => true, 'chef_id' => 7]);
        $other = User::factory()->make(['is_chef' => true, 'chef_id' => 8]);
        $developer = User::factory()->make(['is_developer' => true]);
        $policy = new RecipePolicy();

        $this->assertTrue($policy->update($owner, $recipe));
        $this->assertFalse($policy->update($other, $recipe));
        $this->assertTrue($policy->update($developer, $recipe));
        $this->assertFalse($policy->view(null, $recipe));
    }

    public function testChefCannotAdoptAndDeleteAnotherRecipesImage(): void {
        Storage::fake('public');
        Storage::disk('public')->put('recipes/victim.jpg', 'image');
        $owner = User::factory()->create(['is_chef' => true, 'chef_id' => 7]);
        $victim = Recipe::create(['title' => 'Victim', 'instructions' => 'Cook', 'chef_id' => 8, 'image_filename' => '/storage/recipes/victim.jpg']);
        $recipe = Recipe::create(['title' => 'Mine', 'instructions' => 'Cook', 'chef_id' => 7, 'image_filename' => '/assets/images/recipes/generic_recipe.jpg']);
        $payload = ['title' => 'Mine', 'instructions' => 'Cook', 'image_filename' => $victim->image_filename];

        $this->actingAs($owner)->patchJson("/api/v1/recipes/{$recipe->id}", $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('image_filename');
        $this->postJson('/api/v1/recipes', $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('image_filename');
        $this->deleteJson("/api/v1/recipes/{$recipe->id}/photo")->assertOk();

        Storage::disk('public')->assertExists('recipes/victim.jpg');
        $this->assertSame('/storage/recipes/victim.jpg', $victim->refresh()->image_filename);
    }

    public function testChefAndDeveloperCanKeepReplaceAndRemoveTheRecipesOwnImage(): void {
        Storage::fake('public');
        $owner = User::factory()->create(['is_chef' => true, 'chef_id' => 7]);
        $developer = User::factory()->create(['is_developer' => true]);

        foreach ([$owner, $developer] as $user) {
            $path = "recipes/owned-{$user->id}.jpg";
            Storage::disk('public')->put($path, 'image');
            $recipe = Recipe::create(['title' => 'Mine', 'instructions' => 'Cook', 'chef_id' => 7, 'image_filename' => '/storage/' . $path]);

            $this->actingAs($user)->patchJson("/api/v1/recipes/{$recipe->id}", [
                'title' => 'Updated', 'instructions' => 'Cook', 'image_filename' => $recipe->image_filename,
            ])->assertOk();
            Storage::disk('public')->assertExists($path);

            $this->postJson("/api/v1/recipes/{$recipe->id}/photo", [
                'recipe_photo' => UploadedFile::fake()->image('replacement.jpg'),
            ])->assertOk();
            Storage::disk('public')->assertMissing($path);
            $replacement = substr($recipe->refresh()->image_filename, strlen('/storage/'));
            Storage::disk('public')->assertExists($replacement);

            $this->deleteJson("/api/v1/recipes/{$recipe->id}/photo")->assertOk();
            Storage::disk('public')->assertMissing($replacement);
        }
    }
}
