<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class PageRoutesTest extends TestCase {
    use RefreshDatabase;

    public function testPublicTwigPagesRenderWithSecurityHeaders(): void {
        foreach (['/welcome', '/home', '/about', '/contact', '/privacy', '/recipes', '/sign-in', '/auth/email/finish'] as $uri) {
            $response = $this->get($uri);
            $response->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        }
    }

    public function testContactPrefillsTheSignedInUsersDetailsAndLeavesGuestFieldsEmpty(): void {
        $this->get('/contact')->assertOk()
            ->assertSee('aria-label="Name" value=""', false)
            ->assertSee('aria-label="Email" value=""', false);

        $user = User::factory()->create(['name' => 'Recipe Fan', 'email' => 'fan@example.com']);

        $this->actingAs($user)->get('/contact')->assertOk()
            ->assertSee('aria-label="Name" value="Recipe Fan"', false)
            ->assertSee('aria-label="Email" value="fan@example.com"', false);
    }

    public function testHomeShowsTheTop24CategoriesWithCyclingColors(): void {
        foreach (['RecipeType', 'Cuisine', 'Course'] as $table) {
            DB::table($table)->insert(collect(range(1, 10))->map(fn (int $id) => [
                'id' => $id,
                'name' => $table . ' ' . $id,
                'icon' => 'icon-' . $id,
            ])->all());
        }

        $response = $this->get('/home')->assertOk();
        preg_match('/<ul class="boxed">(.*?)<\/ul>/s', $response->getContent(), $boxed);
        preg_match_all('/<li class="([^"]+)">/', $boxed[1] ?? '', $items);

        $this->assertCount(24, $items[1]);
        $this->assertSame(
            array_map(fn (int $index) => ['light', 'medium', 'dark', 'medium'][$index % 4], range(0, 23)),
            $items[1],
        );
    }

    public function testPrivacyPolicyIsPublicAndLinkedFromTheFooter(): void {
        $this->get('/privacy')
            ->assertOk()
            ->assertSee('September 28, 2026')
            ->assertSee('Information we collect')
            ->assertSee('href="' . route('privacy') . '"', false);
    }

    public function testSignInIsPasswordlessAndContainsCsrfToken(): void {
        $this->get('/sign-in')
            ->assertOk()
            ->assertSee('Continue with Google')
            ->assertSee('Email me a sign-in link')
            ->assertDontSee('type="password"', false)
            ->assertSee('name="_token"', false);
    }

    public function testSignInRejectsExternalReturnUrls(): void {
        $this->get('/sign-in?return=https://example.net/phish')
            ->assertOk()
            ->assertSee('data-return-url="' . route('home') . '"', false)
            ->assertDontSee('example.net', false);
    }

    public function testProtectedPageRedirectsToSignIn(): void {
        $this->get('/account')->assertRedirect('/sign-in?return=%2Faccount');
    }

    public function testHealthEndpointHasNoConfigurationDetails(): void {
        $this->get('/up')->assertOk()->assertDontSee('DB_PASSWORD');
    }

    public function testApiStatusReportsRuntimeAndDatabaseHealth(): void {
        $this->getJson('/api/status')
            ->assertOk()
            ->assertJson([
                'message' => 'System status',
                'data' => [
                    'php_version' => PHP_VERSION,
                    'is_production' => false,
                    'is_db_connected' => true,
                ],
            ]);
    }

    public function testMissingApiRouteUsesTheApiErrorEnvelope(): void {
        $this->getJson('/api/not-a-real-route')
            ->assertNotFound()
            ->assertJson([
                'message' => 'Resource not found.',
                'data' => ['uri' => url('/api/not-a-real-route')],
            ]);
    }

    public function testApiAuthenticationFailureUsesStandardJsonStatus(): void {
        $this->getJson('/api/v1/me')->assertUnauthorized()->assertJson(['message' => 'Unauthenticated.']);
    }

    public function testMissingPageUsesTwigErrorTemplate(): void {
        $this->get('/not-a-real-page')->assertNotFound()->assertSee('Page Not Found');
    }

    public function testMemberCanOpenAndSubmitTheBecomeAChefFlow(): void {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/account?goto=chef')
            ->assertOk()
            ->assertSee('Become a Chef')
            ->assertSee('btn-start-application', false)
            ->assertDontSee('You must verify your account before becoming a chef');

        $this->actingAs($user)
            ->postJson('/api/v1/chef-applications', [
                'already_exists' => false,
                'relationship' => 'family',
                'story' => 'I would like to share family recipes.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.user_id', $user->id);

        $this->assertDatabaseHas('ChefApplication', [
            'user_id' => $user->id,
            'chef_application_status_id' => 1,
            'is_deleted' => false,
        ]);
    }
}
