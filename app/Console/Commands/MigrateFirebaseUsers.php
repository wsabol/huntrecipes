<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Auth;
use Kreait\Firebase\Exception\Auth\UserNotFound;
use RuntimeException;
use Throwable;

class MigrateFirebaseUsers extends Command {
    protected $signature = 'huntrecipes:migrate-firebase-users {--dry-run} {--from-id=0} {--retries=3}';

    protected $description = 'Idempotently create passwordless Firebase identities for legacy users.';

    public function handle(): int {
        $dry = (bool)$this->option('dry-run');
        $failures = 0;
        $duplicates = User::selectRaw('LOWER(email) email_key, COUNT(*) total')->whereNotNull('email')->groupBy('email_key')->having('total', '>', 1)->pluck('email_key');
        if ($duplicates->isNotEmpty()) {
            $this->error('Duplicate emails must be resolved: ' . $duplicates->implode(', '));

            return self::FAILURE;
        }
        $auth = $dry ? null : app(Auth::class);
        User::where('id', '>=', (int)$this->option('from-id'))->orderBy('id')->chunkById(100, function ($users) use ($auth, $dry, &$failures) {
            foreach ($users as $user) {
                if (!filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                    $this->warn("Skipping user {$user->id}: invalid email");
                    $failures++;

                    continue;
                }
                $uid = $user->firebase_uid ?: 'hr_' . $user->id;
                $this->line(($dry ? '[dry-run] ' : '') . "{$user->email} => {$uid}");
                if ($dry) {
                    continue;
                }
                $attempts = max(1, (int)$this->option('retries'));
                while ($attempts-- > 0) {
                    try {
                        try {
                            $record = $auth->getUser($uid);
                            if (strtolower((string)$record->email) !== strtolower($user->email)) {
                                throw new RuntimeException("Firebase UID {$uid} belongs to a different email address.");
                            }
                        } catch (UserNotFound) {
                            try {
                                $record = $auth->getUserByEmail(strtolower($user->email));
                            } catch (UserNotFound) {
                                $record = $auth->createUser(['uid' => $uid, 'email' => strtolower($user->email), 'displayName' => $user->name, 'emailVerified' => (bool)$user->is_email_verified, 'disabled' => !$user->isEnabled()]);
                            }
                        }
                        $record = $auth->updateUser($record->uid, ['displayName' => $user->name, 'emailVerified' => (bool)$user->is_email_verified, 'disabled' => !$user->isEnabled()]);
                        $user->forceFill(['firebase_uid' => $record->uid])->save();
                        Log::info('Firebase user migration succeeded.', ['user_id' => $user->id, 'firebase_uid' => $record->uid]);

                        continue 2;
                    } catch (Throwable $exception) {
                        if ($attempts > 0) {
                            $this->warn("Retrying user {$user->id}: {$exception->getMessage()}");

                            continue;
                        }
                        $failures++;
                        Log::error('Firebase user migration failed.', ['user_id' => $user->id, 'exception' => $exception]);
                        $this->error("Failed user {$user->id}: {$exception->getMessage()}");
                    }
                }
            }
        });

        return $failures ? self::FAILURE : self::SUCCESS;
    }
}
