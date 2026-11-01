<?php

namespace App\Services;

use App\Exceptions\InactiveFirebaseIdentity;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Kreait\Firebase\Auth\UserRecord;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;
use Kreait\Firebase\Exception\Auth\FailedToVerifySessionCookie;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;
use Kreait\Firebase\Exception\Auth\UserNotFound;
use Lcobucci\JWT\UnencryptedToken;
use RuntimeException;

final class FirebaseIdentity {
    private const GENERIC_PROFILE_PICTURE = '/assets/images/users/generic_avatar.jpg';

    public function __construct(private readonly FirebaseAuth $auth) {
    }

    /**
     * @throws FailedToVerifyToken|InactiveFirebaseIdentity
     */
    public function verifyIdToken(string $idToken): UnencryptedToken {
        return $this->assertActive($this->auth->verifyIdToken($idToken));
    }

    /**
     * @throws FailedToVerifySessionCookie|InactiveFirebaseIdentity
     */
    public function verifySessionCookie(string $cookie): UnencryptedToken {
        return $this->assertActive($this->auth->verifySessionCookie($cookie));
    }

    public function createSessionCookie(string $idToken, bool $remember): string {
        $ttl = $remember ? 60 * 60 * 24 * 14 : 60 * 60 * 12;

        return $this->auth->createSessionCookie($idToken, $ttl);
    }

    public function firebaseUser(string $uid): UserRecord {
        return $this->auth->getUser($uid);
    }

    public function unlinkProvider(string $uid, string $provider): UserRecord {
        return $this->auth->unlinkProvider($uid, $provider);
    }

    public function revokeRefreshTokens(string $uid): void {
        $this->auth->revokeRefreshTokens($uid);
    }

    public function resolveLocalUser(UnencryptedToken $token, bool $allowProvision = true): User {
        $uid = (string)$token->claims()->get('sub');
        $email = strtolower((string)$token->claims()->get('email', ''));
        if ($uid === '' || $email === '') {
            throw new RuntimeException('Firebase identity is missing a UID or email address.');
        }

        $existing = User::where('firebase_uid', $uid)->first();
        if ($existing) {
            return $this->synchronize($existing, $token);
        }

        $byEmail = User::whereRaw('LOWER(email) = ?', [$email])->first();
        if ($byEmail?->firebase_uid && $byEmail->firebase_uid !== $uid) {
            throw new RuntimeException('This email is already linked to another identity.');
        }
        if ($byEmail) {
            $byEmail->firebase_uid = $uid;

            return $this->synchronize($byEmail, $token);
        }
        if (!$allowProvision) {
            throw new RuntimeException('No local account is linked to this identity.');
        }

        return DB::transaction(function () use ($uid, $email, $token): User {
            return User::create([
                'firebase_uid' => $uid,
                'name' => (string)$token->claims()->get('name', strstr($email, '@', true) ?: 'HuntRecipes Member'),
                'email' => $email,
                'account_status_id' => 1,
                'profile_picture' => $this->googleProfilePicture($token) ?? self::GENERIC_PROFILE_PICTURE,
                'chef_application_id' => 0,
                'chef_id' => 0,
                'is_chef' => false,
                'is_developer' => false,
                'is_email_verified' => (bool)$token->claims()->get('email_verified', false),
                'date_created' => now(),
            ]);
        });
    }

    /**
     * The SDK's own revocation check does not reject disabled users, so a
     * disabled account would keep a valid session cookie until it expires.
     * One user lookup covers both revocation and the disabled flag.
     */
    private function assertActive(UnencryptedToken $token): UnencryptedToken {
        $uid = (string)$token->claims()->get('sub');
        try {
            $record = $this->auth->getUser($uid);
        } catch (UserNotFound $exception) {
            throw new InactiveFirebaseIdentity('The Firebase user no longer exists.', previous: $exception);
        }

        if ($record->disabled) {
            throw new InactiveFirebaseIdentity('The Firebase user is disabled.');
        }

        $authTime = $token->claims()->get('auth_time');
        $authTimestamp = $authTime instanceof DateTimeInterface ? $authTime->getTimestamp() : (int)$authTime;
        if ($record->tokensValidAfterTime && $authTimestamp < $record->tokensValidAfterTime->getTimestamp()) {
            throw new InactiveFirebaseIdentity('The Firebase session has been revoked.');
        }

        return $token;
    }

    private function synchronize(User $user, UnencryptedToken $token): User {
        $email = strtolower((string)$token->claims()->get('email', $user->email));
        $conflict = User::whereRaw('LOWER(email) = ?', [$email])->whereKeyNot($user->getKey())->exists();
        if ($conflict) {
            throw new RuntimeException('The verified email is already used by another HuntRecipes account.');
        }
        $attributes = [
            'email' => $email,
            'is_email_verified' => (bool)$token->claims()->get('email_verified', false),
        ];
        $profilePicture = $this->googleProfilePicture($token);
        if ($user->profile_picture === self::GENERIC_PROFILE_PICTURE && $profilePicture) {
            $attributes['profile_picture'] = $profilePicture;
        }
        $user->forceFill($attributes)->save();

        return $user;
    }

    private function googleProfilePicture(UnencryptedToken $token): ?string {
        $picture = (string)$token->claims()->get('picture', '');
        if (!filter_var($picture, FILTER_VALIDATE_URL)) {
            return null;
        }

        $scheme = strtolower((string)parse_url($picture, PHP_URL_SCHEME));
        $host = strtolower((string)parse_url($picture, PHP_URL_HOST));
        if ($scheme !== 'https' || ($host !== 'googleusercontent.com' && !str_ends_with($host, '.googleusercontent.com'))) {
            return null;
        }

        return $picture;
    }
}
