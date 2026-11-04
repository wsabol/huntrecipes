<?php

namespace Tests\Feature;

use App\Models\User;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response as Psr7Response;
use GuzzleHttp\Utils as GuzzleUtils;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Kreait\Firebase\Contract\Auth;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Http\HttpClientOptions;
use PHPUnit\Framework\Attributes\Group;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Tests\TestCase;

/**
 * End-to-end authentication against the Firebase Auth Emulator.
 *
 * Nothing in this class mocks Firebase: ID tokens, session cookies, provider
 * links and revocation all come from the emulator, and the Laravel endpoints
 * are exercised with the raw Set-Cookie value a browser would store.
 *
 * Run with: composer test:firebase-emulator
 */
#[Group('firebase-emulator')]
final class FirebaseEmulatorTest extends TestCase {
    use RefreshDatabase;

    private const PROJECT_ID = 'demo-huntrecipes';

    private string $host;

    protected function setUp(): void {
        parent::setUp();

        $host = getenv('FIREBASE_AUTH_EMULATOR_HOST') ?: '';
        if ($host === '') {
            $this->markTestSkipped('FIREBASE_AUTH_EMULATOR_HOST is not set; run composer test:firebase-emulator.');
        }
        $this->host = $host;

        $this->emulator()->delete($this->emulatorUrl('/accounts'))->throw();
        $this->app->instance(Auth::class, $this->emulatorAuth());
    }

    public function testEmailLinkSignInEstablishesASessionThatWorksOnWebAndApiRoutes(): void {
        $idToken = $this->signInWithEmailLink('cook@example.com');

        $cookie = $this->exchange($idToken)->assertOk()->json('data');
        $this->assertSame('cook@example.com', $cookie['email'] ?? null);

        $session = $this->sessionCookie($this->lastResponse);
        $this->assertTrue($session->isHttpOnly());
        $this->assertSame('lax', $session->getSameSite());
        $this->assertSame('/', $session->getPath());

        $this->asBrowser($session)->get('/account')->assertOk();
        $this->asBrowser($session)->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.email', 'cook@example.com');

        $user = User::where('email', 'cook@example.com')->sole();
        $this->assertNotEmpty($user->firebase_uid);
    }

    public function testRememberedSessionsLastFourteenDaysAndOthersTwelveHours(): void {
        $remembered = $this->claims($this->sessionCookie($this->exchange($this->signInWithEmailLink('long@example.com'), true)));
        $this->assertSame(60 * 60 * 24 * 14, $remembered['exp'] - $remembered['iat']);

        $short = $this->sessionCookie($this->exchange($this->signInWithEmailLink('short@example.com')));
        $this->assertSame(60 * 60 * 12, $this->claims($short)['exp'] - $this->claims($short)['iat']);
        $this->assertSame(0, $short->getExpiresTime(), 'A non-remembered session must be a browser-session cookie.');
    }

    public function testEmailLinksAreSingleUse(): void {
        $this->sendEmailLink('once@example.com');
        $code = $this->latestOobCode('once@example.com');

        $this->identityToolkit('accounts:signInWithEmailLink', ['email' => 'once@example.com', 'oobCode' => $code])->throw();
        $replay = $this->identityToolkit('accounts:signInWithEmailLink', ['email' => 'once@example.com', 'oobCode' => $code]);

        $this->assertTrue($replay->failed());
        $this->assertStringContainsString('INVALID_OOB_CODE', $replay->json('error.message'));
    }

    public function testEmailLinkForADifferentAddressIsRejected(): void {
        $this->sendEmailLink('owner@example.com');
        $code = $this->latestOobCode('owner@example.com');

        $response = $this->identityToolkit('accounts:signInWithEmailLink', ['email' => 'attacker@example.com', 'oobCode' => $code]);

        $this->assertTrue($response->failed());
    }

    public function testUnverifiedFirebaseIdentityCannotCreateASession(): void {
        $idToken = $this->identityToolkit('accounts:signUp', [
            'email' => 'unverified@example.com',
            'password' => 'not-used-by-huntrecipes',
            'returnSecureToken' => true,
        ])->throw()->json('idToken');

        $this->exchange($idToken)->assertForbidden();
        $this->assertDatabaseMissing('User', ['email' => 'unverified@example.com']);
    }

    public function testStaleSignInCannotCreateASession(): void {
        $idToken = $this->signInWithEmailLink('stale@example.com');

        $this->travel(6)->minutes();

        $this->exchange($idToken)->assertUnauthorized();
    }

    public function testGoogleSignInProvisionsAnAccountWithTheGooglePicture(): void {
        $picture = 'https://lh3.googleusercontent.com/a/chef';
        $idToken = $this->signInWithGoogle('chef@example.com', 'Google Chef', $picture);

        $this->exchange($idToken)->assertOk();

        $user = User::where('email', 'chef@example.com')->sole();
        $this->assertSame('Google Chef', $user->name);
        $this->assertSame($picture, $user->profile_picture);
    }

    public function testLinkingGoogleKeepsOneLocalAccountAndListsBothProviders(): void {
        $emailToken = $this->signInWithEmailLink('linked@example.com');
        $session = $this->sessionCookie($this->exchange($emailToken)->assertOk());

        $this->signInWithGoogle('linked@example.com', 'Linked', null, $emailToken);

        $providers = $this->asBrowser($session)->getJson('/api/v1/me/providers')->assertOk()->json('data.providers');
        $this->assertEqualsCanonicalizing(['password', 'google.com'], $providers);
        $this->assertSame(1, User::where('email', 'linked@example.com')->count());
    }

    public function testUnlinkingGoogleRevokesExistingSessions(): void {
        $emailToken = $this->signInWithEmailLink('unlink@example.com');
        $this->signInWithGoogle('unlink@example.com', 'Unlink', null, $emailToken);
        $session = $this->sessionCookie($this->exchange($emailToken)->assertOk());

        // Firebase revocation has one-second granularity.
        sleep(1);
        $this->asBrowser($session)
            ->deleteJson('/api/v1/me/providers/google', ['id_token' => $emailToken])
            ->assertOk()
            ->assertCookieExpired(config('services.firebase.session_cookie'));

        $this->asBrowser($session)->getJson('/api/v1/me')->assertUnauthorized();
        $user = $this->firebaseAuth()->getUser(User::where('email', 'unlink@example.com')->sole()->firebase_uid);
        $this->assertSame(['password'], collect($user->providerData)->pluck('providerId')->all());
    }

    public function testTheLastProviderCannotBeUnlinked(): void {
        $googleToken = $this->signInWithGoogle('only-google@example.com', 'Only Google');
        $session = $this->sessionCookie($this->exchange($googleToken)->assertOk());

        $this->asBrowser($session)
            ->deleteJson('/api/v1/me/providers/google', ['id_token' => $googleToken])
            ->assertUnprocessable();
    }

    public function testRevokedSessionIsRejectedAndCleared(): void {
        $idToken = $this->signInWithEmailLink('revoked@example.com');
        $session = $this->sessionCookie($this->exchange($idToken)->assertOk());
        $this->asBrowser($session)->getJson('/api/v1/me')->assertOk();

        sleep(1);
        $this->firebaseAuth()->revokeRefreshTokens(User::where('email', 'revoked@example.com')->sole()->firebase_uid);

        $this->asBrowser($session)->getJson('/api/v1/me')
            ->assertUnauthorized()
            ->assertCookieExpired(config('services.firebase.session_cookie'));
        $this->asBrowser($session)->get('/account')->assertRedirect();
    }

    public function testDisabledFirebaseUserIsRejectedEvenWithAnUnexpiredSession(): void {
        $idToken = $this->signInWithEmailLink('disabled@example.com');
        $session = $this->sessionCookie($this->exchange($idToken)->assertOk());

        $this->firebaseAuth()->disableUser(User::where('email', 'disabled@example.com')->sole()->firebase_uid);

        $this->asBrowser($session)->getJson('/api/v1/me')
            ->assertUnauthorized()
            ->assertCookieExpired(config('services.firebase.session_cookie'));
    }

    public function testDeletedFirebaseUserIsRejected(): void {
        $session = $this->sessionCookie($this->exchange($this->signInWithEmailLink('deleted@example.com'))->assertOk());

        $this->firebaseAuth()->deleteUser(User::where('email', 'deleted@example.com')->sole()->firebase_uid);

        $this->asBrowser($session)->getJson('/api/v1/me')->assertUnauthorized();
    }

    /**
     * The emulator does not sign tokens, so this cannot prove signature
     * verification; it proves an unsigned token for a user Firebase does not
     * know is rejected before a local account is created.
     */
    public function testUnsignedTokenForUnknownUserIsRejectedWithoutProvisioning(): void {
        $forged = implode('.', [
            rtrim(strtr(base64_encode(json_encode(['alg' => 'none', 'typ' => 'JWT'])), '+/', '-_'), '='),
            rtrim(strtr(base64_encode(json_encode([
                'iss' => 'https://securetoken.google.com/' . self::PROJECT_ID,
                'aud' => self::PROJECT_ID,
                'sub' => 'never-signed-in',
                'email' => 'forged@example.com',
                'email_verified' => true,
                'iat' => time(),
                'exp' => time() + 3600,
                'auth_time' => time(),
            ])), '+/', '-_'), '='),
            '',
        ]);

        $this->exchange($forged)->assertUnauthorized();
        $this->assertDatabaseMissing('User', ['email' => 'forged@example.com']);
    }

    public function testDisabledFirebaseUserCannotCreateASession(): void {
        $idToken = $this->signInWithEmailLink('disabled-new@example.com');
        $uid = $this->claims($idToken)['sub'];
        $this->firebaseAuth()->disableUser($uid);

        $this->exchange($idToken)->assertUnauthorized();
        $this->assertDatabaseMissing('User', ['email' => 'disabled-new@example.com']);
    }

    public function testSignOutClearsTheCookie(): void {
        $session = $this->sessionCookie($this->exchange($this->signInWithEmailLink('bye@example.com'))->assertOk());

        $this->asBrowser($session)->deleteJson('/api/v1/auth/session')
            ->assertOk()
            ->assertCookieExpired(config('services.firebase.session_cookie'));
    }

    private ?TestResponse $lastResponse = null;

    private function exchange(string $idToken, bool $remember = false): TestResponse {
        return $this->lastResponse = $this->postJson('/api/v1/auth/session', ['id_token' => $idToken, 'remember' => $remember]);
    }

    /**
     * Replays the cookie exactly as the browser received it.
     */
    private function asBrowser(Cookie $cookie): self {
        // Each request must authenticate from the cookie alone, not from a guard
        // user left behind by an earlier request in the same test.
        $this->app['auth']->forgetGuards();
        $this->defaultCookies = [];
        $this->unencryptedCookies = [];

        return $this->withCredentials()->withUnencryptedCookie($cookie->getName(), (string)$cookie->getValue());
    }

    private function sessionCookie(TestResponse $response): Cookie {
        $name = config('services.firebase.session_cookie');
        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->getName() === $name) {
                return $cookie;
            }
        }
        $this->fail("The response did not set the {$name} cookie.");
    }

    /** @return array<string, mixed> */
    private function claims(Cookie|string $jwt): array {
        $value = $jwt instanceof Cookie ? $this->firebaseValue($jwt) : $jwt;
        $payload = explode('.', $value)[1] ?? '';

        return json_decode(base64_decode(strtr($payload, '-_', '+/')), true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * Decrypts the cookie when Laravel's cookie encryption wrapped it.
     */
    private function firebaseValue(Cookie $cookie): string {
        $value = (string)$cookie->getValue();
        if (substr_count($value, '.') === 2) {
            return $value;
        }

        return \Illuminate\Cookie\CookieValuePrefix::remove(app('encrypter')->decrypt($value, false));
    }

    private function signInWithEmailLink(string $email): string {
        $this->sendEmailLink($email);

        return $this->identityToolkit('accounts:signInWithEmailLink', [
            'email' => $email,
            'oobCode' => $this->latestOobCode($email),
        ])->throw()->json('idToken');
    }

    private function sendEmailLink(string $email): void {
        $this->identityToolkit('accounts:sendOobCode', [
            'requestType' => 'EMAIL_SIGNIN',
            'email' => $email,
            'continueUrl' => 'http://localhost/auth/email/finish',
            'canHandleCodeInApp' => true,
        ])->throw();
    }

    private function latestOobCode(string $email): string {
        $codes = collect($this->emulator()->get($this->emulatorUrl('/oobCodes'))->throw()->json('oobCodes'))
            ->where('email', $email)
            ->where('requestType', 'EMAIL_SIGNIN');

        return $codes->last()['oobCode'];
    }

    /**
     * The emulator accepts an unsigned JSON IdP credential in place of a Google ID token.
     */
    private function signInWithGoogle(string $email, string $name, ?string $picture = null, ?string $linkTo = null): string {
        $claims = array_filter([
            'sub' => 'google-' . md5($email),
            'email' => $email,
            'email_verified' => true,
            'name' => $name,
            'picture' => $picture,
        ], fn ($value) => $value !== null);

        return $this->identityToolkit('accounts:signInWithIdp', array_filter([
            'postBody' => http_build_query(['id_token' => json_encode($claims), 'providerId' => 'google.com']),
            'requestUri' => 'http://localhost',
            'returnSecureToken' => true,
            'returnIdpCredential' => true,
            'idToken' => $linkTo,
        ]))->throw()->json('idToken');
    }

    private function identityToolkit(string $method, array $body): \Illuminate\Http\Client\Response {
        // Client (browser) calls are unauthenticated; only emulator admin calls use the owner token.
        return Http::acceptJson()->post("http://{$this->host}/identitytoolkit.googleapis.com/v1/{$method}?key=fake-api-key", $body);
    }

    private function emulator(): PendingRequest {
        return Http::withToken('owner')->acceptJson();
    }

    private function emulatorUrl(string $path): string {
        return "http://{$this->host}/emulator/v1/projects/" . self::PROJECT_ID . $path;
    }

    private function firebaseAuth(): Auth {
        return $this->app->make(Auth::class);
    }

    /**
     * Builds the real SDK client pointed at the emulator. The SDK always fetches
     * a Google OAuth access token first; that single request is answered
     * locally so the suite needs no real credentials or network access.
     */
    private function emulatorAuth(): Auth {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($key, $privateKey);

        $network = GuzzleUtils::chooseHandler();
        $handler = static function (RequestInterface $request, array $options) use ($network) {
            if ($request->getUri()->getHost() === 'oauth2.googleapis.com') {
                return Create::promiseFor(new Psr7Response(200, ['Content-Type' => 'application/json'], json_encode([
                    'access_token' => 'owner',
                    'expires_in' => 3600,
                    'token_type' => 'Bearer',
                ])));
            }

            return $network($request, $options);
        };

        return (new Factory())
            ->withProjectId(self::PROJECT_ID)
            ->withServiceAccount([
                'type' => 'service_account',
                'project_id' => self::PROJECT_ID,
                'private_key_id' => 'emulator',
                'private_key' => $privateKey,
                'client_email' => 'emulator@' . self::PROJECT_ID . '.iam.gserviceaccount.com',
                'client_id' => '1',
            ])
            ->withHttpClientOptions(HttpClientOptions::default()->withGuzzleHandler($handler))
            ->createAuth();
    }
}
