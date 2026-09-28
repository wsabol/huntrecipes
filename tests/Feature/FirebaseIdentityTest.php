<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\FirebaseIdentity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kreait\Firebase\Contract\Auth;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\UnencryptedToken;
use RuntimeException;
use Tests\TestCase;

final class FirebaseIdentityTest extends TestCase {
    use RefreshDatabase;

    public function testVerifiedIdentityProvisionsAPasswordlessLocalUser(): void {
        $picture = 'https://lh3.googleusercontent.com/a/profile-photo';
        $user = $this->identity()->resolveLocalUser($this->token('firebase-new', 'new@example.com', 'New Member', $picture));

        $this->assertSame('firebase-new', $user->firebase_uid);
        $this->assertSame('new@example.com', $user->email);
        $this->assertSame('New Member', $user->name);
        $this->assertSame($picture, $user->profile_picture);
        $this->assertNull($user->password);
    }

    public function testGooglePictureReplacesTheGenericAvatarOnSignIn(): void {
        $user = User::factory()->create([
            'firebase_uid' => 'generic-avatar',
            'profile_picture' => '/assets/images/users/generic_avatar.jpg',
        ]);
        $picture = 'https://lh3.googleusercontent.com/a/new-profile-photo';

        $this->identity()->resolveLocalUser($this->token('generic-avatar', $user->email, picture: $picture));

        $this->assertSame($picture, $user->refresh()->profile_picture);
    }

    public function testGooglePictureDoesNotReplaceACustomAvatar(): void {
        $user = User::factory()->create([
            'firebase_uid' => 'custom-avatar',
            'profile_picture' => '/storage/users/custom-avatar.jpg',
        ]);

        $this->identity()->resolveLocalUser($this->token(
            'custom-avatar',
            $user->email,
            picture: 'https://lh3.googleusercontent.com/a/google-profile-photo',
        ));

        $this->assertSame('/storage/users/custom-avatar.jpg', $user->refresh()->profile_picture);
    }

    public function testExistingEmailIsLinkedWithoutCreatingADuplicate(): void {
        $legacy = User::factory()->create(['email' => 'legacy@example.com', 'firebase_uid' => null]);

        $resolved = $this->identity()->resolveLocalUser($this->token('hr_' . $legacy->id, 'legacy@example.com'));

        $this->assertTrue($legacy->is($resolved));
        $this->assertSame('hr_' . $legacy->id, $legacy->refresh()->firebase_uid);
        $this->assertSame(1, User::where('email', 'legacy@example.com')->count());
    }

    public function testEmailCollisionWithAnotherFirebaseUidIsRejected(): void {
        User::factory()->create(['email' => 'collision@example.com', 'firebase_uid' => 'existing-uid']);
        $this->expectException(RuntimeException::class);

        $this->identity()->resolveLocalUser($this->token('different-uid', 'collision@example.com'));
    }

    private function identity(): FirebaseIdentity {
        return new FirebaseIdentity($this->createMock(Auth::class));
    }

    private function token(string $uid, string $email, string $name = 'Member', ?string $picture = null): UnencryptedToken {
        $configuration = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText(str_repeat('x', 32)));

        $builder = $configuration->builder()
            ->relatedTo($uid)
            ->withClaim('email', $email)
            ->withClaim('name', $name)
            ->withClaim('email_verified', true);
        if ($picture) {
            $builder = $builder->withClaim('picture', $picture);
        }

        return $builder->getToken($configuration->signer(), $configuration->signingKey());
    }
}
