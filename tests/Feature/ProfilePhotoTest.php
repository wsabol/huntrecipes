<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class ProfilePhotoTest extends TestCase {
    use RefreshDatabase;

    public function testGoogleLargePhotoSupportsExistingUrlFormats(): void {
        foreach ([
            'https://lh3.googleusercontent.com/a/photo=s96-c' => 'https://lh3.googleusercontent.com/a/photo=s608-c',
            'https://lh3.googleusercontent.com/a/photo=s96-c-mo' => 'https://lh3.googleusercontent.com/a/photo=s608-c-mo',
            'https://lh3.googleusercontent.com/a/s96/photo.jpg' => 'https://lh3.googleusercontent.com/a/s608/photo.jpg',
            'https://lh3.googleusercontent.com/a/photo.jpg?sz=96&other=1' => 'https://lh3.googleusercontent.com/a/photo.jpg?sz=608&other=1',
            'https://lh3.googleusercontent.com/a/photo' => 'https://lh3.googleusercontent.com/a/photo=s608-c',
            '/assets/images/users/generic_avatar.jpg' => '/assets/images/users/generic_avatar.jpg',
            'https://googleusercontent.com.evil.test/a/photo=s96' => 'https://googleusercontent.com.evil.test/a/photo=s96',
        ] as $small => $large) {
            $user = new User(['profile_picture' => $small]);
            $this->assertSame($large, $user->profile_picture_lg);
            $this->assertSame($large, $user->toArray()['profile_picture_lg']);
            $this->assertSame($small, $user->profile_picture);
        }
    }

    public function testUploadCreatesBothSquareSizesAndReplacesBothOldFiles(): void {
        Storage::fake('public');
        $user = User::factory()->create();
        $response = $this->actingAs($user)->postJson('/api/v1/me/photo', [
            'profile_photo' => UploadedFile::fake()->image('portrait.png', 900, 700),
        ])->assertOk();
        $paths = [];
        foreach (['profile_picture' => 96, 'profile_picture_lg' => 608] as $field => $size) {
            $path = substr($response->json('data.' . $field), strlen('/storage/'));
            $paths[] = $path;
            Storage::disk('public')->assertExists($path);
            $dimensions = getimagesizefromstring(Storage::disk('public')->get($path));
            $this->assertSame([$size, $size], array_slice($dimensions, 0, 2));
            $this->assertSame('image/jpeg', $dimensions['mime']);
        }
        $this->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.profile_picture_lg', $response->json('data.profile_picture_lg'));
        $this->get('/account')->assertOk()->assertSee('src="' . $response->json('data.profile_picture_lg') . '"', false);
        $this->postJson('/api/v1/me/photo', [
            'profile_photo' => UploadedFile::fake()->image('replacement.jpg', 608, 608),
        ])->assertOk();
        Storage::disk('public')->assertMissing($paths);
    }

    public function testLegacyUploadFallsBackWithoutABrokenLargeUrl(): void {
        Storage::fake('public');
        $user = new User(['profile_picture' => '/storage/users/legacy.jpg']);
        $this->assertSame($user->profile_picture, $user->profile_picture_lg);
    }
}
