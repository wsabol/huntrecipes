<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Twig\TwigRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TwigContextTest extends TestCase {
    use RefreshDatabase;

    public function testSharedContextForGuest(): void {
        $html = app(TwigRenderer::class)->html('testing/context.twig', ['page_title' => 'Test']);
        $this->assertStringContainsString('Test|0|' . now()->year, $html);
    }

    public function testSharedContextUsesAuthenticatedUser(): void {
        $user = User::factory()->create();
        $this->actingAs($user);
        $html = app(TwigRenderer::class)->html('testing/context.twig', ['page_title' => 'Account']);
        $this->assertStringContainsString('Account|' . $user->id, $html);
    }

    public function testExplicitContextIsAvailableToTwig(): void {
        $html = app(TwigRenderer::class)->html('testing/context.twig', ['custom_key' => 'custom-value']);
        $this->assertStringContainsString('custom-value', $html);
    }
}
