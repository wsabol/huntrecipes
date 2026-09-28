<?php

namespace Tests\Feature;

use App\Http\Middleware\ResolveFirebaseUser;
use App\Models\User;
use App\Services\FirebaseIdentity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Testing\TestResponse;
use Kreait\Firebase\Contract\Auth;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\UnencryptedToken;
use Tests\TestCase;

final class FirebaseSessionExchangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_recent_verified_token_is_exchanged_for_secure_server_cookie(): void
    {
        $this->bindIdentity($this->token('session-user', 'session@example.com'), 'firebase-session-cookie');

        $this->postJson('/api/v1/auth/session', ['id_token' => 'id-token', 'remember' => true])
            ->assertOk()
            ->assertCookie(config('services.firebase.session_cookie'));

        $this->assertDatabaseHas('User', ['firebase_uid' => 'session-user', 'email' => 'session@example.com']);
    }

    public function test_unverified_email_is_rejected(): void
    {
        $this->bindIdentity($this->token('unverified', 'unverified@example.com', false));

        $this->postJson('/api/v1/auth/session', ['id_token' => 'id-token'])
            ->assertForbidden();
    }

    public function test_stale_authentication_is_rejected(): void
    {
        $this->bindIdentity($this->token('stale', 'stale@example.com', true, now()->subHour()->timestamp));

        $this->postJson('/api/v1/auth/session', ['id_token' => 'id-token'])
            ->assertUnauthorized();
    }

    public function test_disabled_local_account_is_rejected(): void
    {
        User::factory()->create(['firebase_uid' => 'disabled', 'email' => 'disabled@example.com', 'account_status_id' => 2]);
        $this->bindIdentity($this->token('disabled', 'disabled@example.com'));

        $this->postJson('/api/v1/auth/session', ['id_token' => 'id-token'])
            ->assertForbidden();
    }

    public function test_invalid_session_cookie_is_cleared_on_protected_routes(): void
    {
        $auth = $this->createMock(Auth::class);
        $auth->method('verifySessionCookie')->willThrowException(
            new \RuntimeException('Invalid Firebase session.'),
        );
        $this->app->instance(FirebaseIdentity::class, new FirebaseIdentity($auth));

        $request = Request::create('/api/v1/me', 'GET', cookies: [
            config('services.firebase.session_cookie') => 'invalid-session',
        ]);
        $request->headers->set('Accept', 'application/json');
        $response = app(ResolveFirebaseUser::class)->handle(
            $request,
            fn () => response()->json(['message' => 'should not run']),
            'required',
        );

        TestResponse::fromBaseResponse($response)
            ->assertUnauthorized()
            ->assertCookieExpired(config('services.firebase.session_cookie'));
    }

    private function bindIdentity(UnencryptedToken $token, string $cookie = 'unused'): void
    {
        $auth = $this->createMock(Auth::class);
        $auth->method('verifyIdToken')->willReturn($token);
        $auth->method('createSessionCookie')->willReturn($cookie);
        $this->app->instance(FirebaseIdentity::class, new FirebaseIdentity($auth));
    }

    private function token(string $uid, string $email, bool $verified = true, ?int $authTime = null): UnencryptedToken
    {
        $configuration = Configuration::forSymmetricSigner(new Sha256, InMemory::plainText(str_repeat('x', 32)));

        return $configuration->builder()
            ->relatedTo($uid)
            ->withClaim('email', $email)
            ->withClaim('email_verified', $verified)
            ->withClaim('auth_time', $authTime ?? now()->timestamp)
            ->getToken($configuration->signer(), $configuration->signingKey());
    }
}
