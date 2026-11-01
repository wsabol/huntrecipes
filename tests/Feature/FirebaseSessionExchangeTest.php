<?php

namespace Tests\Feature;

use App\Http\Middleware\ResolveFirebaseUser;
use App\Models\User;
use App\Services\FirebaseIdentity;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Testing\TestResponse;
use Kreait\Firebase\Auth\UserRecord;
use Kreait\Firebase\Contract\Auth;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\UnencryptedToken;
use Tests\TestCase;

final class FirebaseSessionExchangeTest extends TestCase {
    use RefreshDatabase;

    public function testRecentVerifiedTokenIsExchangedForSecureServerCookie(): void {
        $this->bindIdentity($this->token('session-user', 'session@example.com'), 'firebase-session-cookie');

        $this->postJson('/api/v1/auth/session', ['id_token' => 'id-token', 'remember' => true])
            ->assertOk()
            ->assertCookie(config('services.firebase.session_cookie'));

        $this->assertDatabaseHas('User', ['firebase_uid' => 'session-user', 'email' => 'session@example.com']);
    }

    public function testUnverifiedEmailIsRejected(): void {
        $this->bindIdentity($this->token('unverified', 'unverified@example.com', false));

        $this->postJson('/api/v1/auth/session', ['id_token' => 'id-token'])
            ->assertForbidden();
    }

    public function testStaleAuthenticationIsRejected(): void {
        $this->bindIdentity($this->token('stale', 'stale@example.com', true, now()->subHour()->timestamp));

        $this->postJson('/api/v1/auth/session', ['id_token' => 'id-token'])
            ->assertUnauthorized();
    }

    public function testDisabledLocalAccountIsRejected(): void {
        User::factory()->create(['firebase_uid' => 'disabled', 'email' => 'disabled@example.com', 'account_status_id' => 2]);
        $this->bindIdentity($this->token('disabled', 'disabled@example.com'));

        $this->postJson('/api/v1/auth/session', ['id_token' => 'id-token'])
            ->assertForbidden();
    }

    public function testInvalidSessionCookieIsClearedOnProtectedRoutes(): void {
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

    public function testCookieIsSecureHttpOnlyAndLaxInProduction(): void {
        $this->app['env'] = 'production';
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->bindIdentity($this->token('prod-user', 'prod@example.com'), 'firebase-session-cookie');

        $cookie = collect($this->postJson('/api/v1/auth/session', ['id_token' => 'id-token'])->assertOk()->headers->getCookies())
            ->firstWhere(fn ($cookie) => $cookie->getName() === config('services.firebase.session_cookie'));

        $this->assertTrue($cookie->isSecure());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('lax', $cookie->getSameSite());
    }

    public function testDisabledFirebaseUserIsRejectedBeforeProvisioning(): void {
        $this->bindIdentity($this->token('fb-disabled', 'fb-disabled@example.com'), record: ['disabled' => true]);

        $this->postJson('/api/v1/auth/session', ['id_token' => 'id-token'])
            ->assertUnauthorized();

        $this->assertDatabaseMissing('User', ['email' => 'fb-disabled@example.com']);
    }

    public function testTokenIssuedBeforeRevocationIsRejected(): void {
        $this->bindIdentity(
            $this->token('revoked', 'revoked@example.com', true, now()->subMinute()->timestamp),
            record: ['validSince' => (string) now()->timestamp],
        );

        $this->postJson('/api/v1/auth/session', ['id_token' => 'id-token'])
            ->assertUnauthorized();
    }

    public function testUnverifiableTokenIsUnauthorizedNotAServerError(): void {
        $auth = $this->createMock(Auth::class);
        $auth->method('verifyIdToken')->willThrowException(new FailedToVerifyToken('bad signature'));
        $this->app->instance(FirebaseIdentity::class, new FirebaseIdentity($auth));

        $this->postJson('/api/v1/auth/session', ['id_token' => 'id-token'])
            ->assertUnauthorized();
    }

    private function bindIdentity(UnencryptedToken $token, string $cookie = 'unused', array $record = []): void {
        $auth = $this->createMock(Auth::class);
        $auth->method('verifyIdToken')->willReturn($token);
        $auth->method('getUser')->willReturn(UserRecord::fromResponseData([
            'localId' => (string) $token->claims()->get('sub'),
            'createdAt' => (string) (now()->subDay()->timestamp * 1000),
            ...$record,
        ]));
        $auth->method('createSessionCookie')->willReturn($cookie);
        $this->app->instance(FirebaseIdentity::class, new FirebaseIdentity($auth));
    }

    private function token(string $uid, string $email, bool $verified = true, ?int $authTime = null): UnencryptedToken {
        $configuration = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText(str_repeat('x', 32)));

        return $configuration->builder()
            ->relatedTo($uid)
            ->withClaim('email', $email)
            ->withClaim('email_verified', $verified)
            ->withClaim('auth_time', $authTime ?? now()->timestamp)
            ->getToken($configuration->signer(), $configuration->signingKey());
    }
}
