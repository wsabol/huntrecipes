<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MigrateFirebaseUsersCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_needs_no_firebase_credentials_and_writes_nothing(): void
    {
        $user = User::factory()->create(['firebase_uid' => null]);

        $this->artisan('huntrecipes:migrate-firebase-users', ['--dry-run' => true])
            ->expectsOutputToContain("{$user->email} => hr_{$user->id}")
            ->assertSuccessful();

        $this->assertNull($user->refresh()->firebase_uid);
    }

    public function test_invalid_email_fails_validation_during_dry_run(): void
    {
        User::factory()->create(['email' => 'not-an-email', 'firebase_uid' => null]);

        $this->artisan('huntrecipes:migrate-firebase-users', ['--dry-run' => true])
            ->expectsOutputToContain('invalid email')
            ->assertFailed();
    }
}
