<?php

namespace App\Policies;

use App\Models\Recipe;
use App\Models\User;

class RecipePolicy {
    public function view(?User $user, Recipe $recipe): bool {
        return $recipe->published_flag || $this->update($user, $recipe);
    }

    public function create(User $user): bool {
        return $user->is_chef || $user->is_developer;
    }

    public function update(?User $user, Recipe $recipe): bool {
        return (bool)$user && ($user->is_developer || ($user->is_chef && (int)$user->chef_id === (int)$recipe->chef_id));
    }

    public function delete(User $user, Recipe $recipe): bool {
        return $this->update($user, $recipe);
    }

    public function generateImage(User $user, Recipe $recipe): bool {
        return $user->is_developer;
    }
}
