<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Twig\TwigRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TwigContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_shared_context_for_guest(): void
    {
        $html = app(TwigRenderer::class)->html('testing/context.twig', ['page_title' => 'Test']);
        $this->assertStringContainsString('Test|0|'.now()->year, $html);
    }

    public function test_shared_context_uses_authenticated_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $html = app(TwigRenderer::class)->html('testing/context.twig', ['page_title' => 'Account']);
        $this->assertStringContainsString('Account|'.$user->id, $html);
    }

    public function test_explicit_context_is_available_to_twig(): void
    {
        $html = app(TwigRenderer::class)->html('testing/context.twig', ['custom_key' => 'custom-value']);
        $this->assertStringContainsString('custom-value', $html);
    }
}
