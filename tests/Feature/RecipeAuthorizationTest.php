<?php

namespace Tests\Feature;

use App\Models\Recipe;
use App\Models\User;
use App\Policies\RecipePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RecipeAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_recipe_changes_are_limited_to_owner_or_developer(): void
    {
        $recipe = new Recipe(['chef_id' => 7, 'published_flag' => false]);
        $owner = User::factory()->make(['is_chef' => true, 'chef_id' => 7]);
        $other = User::factory()->make(['is_chef' => true, 'chef_id' => 8]);
        $developer = User::factory()->make(['is_developer' => true]);
        $policy = new RecipePolicy;

        $this->assertTrue($policy->update($owner, $recipe));
        $this->assertFalse($policy->update($other, $recipe));
        $this->assertTrue($policy->update($developer, $recipe));
        $this->assertFalse($policy->view(null, $recipe));
    }
}
