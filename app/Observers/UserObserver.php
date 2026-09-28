<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Auth;
use Throwable;

final class UserObserver {
    public function updated(User $user): void {
        if (!$user->wasChanged('account_status_id') || $user->isEnabled() || !$user->firebase_uid) {
            return;
        }

        try {
            app(Auth::class)->revokeRefreshTokens($user->firebase_uid);
            Log::notice('Firebase sessions revoked for disabled account.', ['user_id' => $user->id]);
        } catch (Throwable $exception) {
            Log::error('Unable to revoke Firebase sessions for disabled account.', ['user_id' => $user->id, 'exception' => $exception]);
        }
    }
}
