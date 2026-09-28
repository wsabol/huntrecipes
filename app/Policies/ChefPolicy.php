<?php

namespace App\Policies;

use App\Models\Chef;
use App\Models\User;

final class ChefPolicy {
    public function update(User $user, Chef $chef): bool {
        return (bool)$user->is_developer || ((bool)$user->is_chef && (int)$user->chef_id === (int)$chef->id);
    }
}
