<?php

namespace App\Policies;

use App\Models\User;

final class UserPolicy {
    public function viewAny(User $user): bool {
        return (bool)$user->is_developer;
    }

    public function update(User $user, User $profile): bool {
        return $user->is($profile);
    }

    public function uploadPhoto(User $user, User $profile): bool {
        return $this->update($user, $profile);
    }
}
