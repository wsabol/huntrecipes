<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MigrateFirebaseUsersCommandTest extends TestCase {
    use RefreshDatabase;

    public function testDryRunNeedsNoFirebaseCredentialsAndWritesNothing(): void {
        $user = User::factory()->create(['firebase_uid' => null]);

        $this->artisan('huntrecipes:migrate-firebase-users', ['--dry-run' => true])
            ->expectsOutputToContain("{$user->email} => hr_{$user->id}")
            ->assertSuccessful();

        $this->assertNull($user->refresh()->firebase_uid);
    }

    public function testInvalidEmailFailsValidationDuringDryRun(): void {
        User::factory()->create(['email' => 'not-an-email', 'firebase_uid' => null]);

        $this->artisan('huntrecipes:migrate-firebase-users', ['--dry-run' => true])
            ->expectsOutputToContain('invalid email')
            ->assertFailed();
    }
}
