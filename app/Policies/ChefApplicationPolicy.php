<?php

namespace App\Policies;

use App\Models\ChefApplication;
use App\Models\User;

final class ChefApplicationPolicy {
    public function viewAny(User $user): bool {
        return (bool)$user->is_developer;
    }

    public function create(User $user): bool {
        return !$user->is_chef;
    }

    public function delete(User $user, ChefApplication $application): bool {
        return (bool)$user->is_developer || $application->user_id === $user->id;
    }

    public function review(User $user, ChefApplication $application): bool {
        return (bool)$user->is_developer;
    }
}
