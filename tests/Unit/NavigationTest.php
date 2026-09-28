<?php

namespace Tests\Unit;

use App\Models\User;
use App\Support\Navigation;
use Tests\TestCase;

final class NavigationTest extends TestCase
{
    public function test_main_navigation_for_guest(): void
    {
        $items = app(Navigation::class)->main(null);
        $this->assertSame(['Home', 'About', 'Recipes', 'Contact'], array_column($items, 'title'));
        $this->assertArrayHasKey('is_active', $items[0]);
    }

    public function test_main_navigation_for_developer(): void
    {
        $items = app(Navigation::class)->main(User::factory()->make(['is_developer' => true]));
        $admin = collect($items)->firstWhere('title', 'Admin');
        $this->assertSame('Chef Approval', $admin['submenu'][0]['title']);
    }

    public function test_user_navigation_for_guest(): void
    {
        $items = app(Navigation::class)->user(null);
        $this->assertCount(1, $items);
        $this->assertSame('Sign in', $items[0]['title']);
    }

    public function test_user_navigation_for_member(): void
    {
        $items = app(Navigation::class)->user(User::factory()->make());
        $this->assertSame(['Favorites', 'My Account'], array_column($items, 'title'));
    }

    public function test_user_navigation_for_chef(): void
    {
        $items = app(Navigation::class)->user(User::factory()->make(['is_chef' => true]));
        $this->assertContains('Submit a recipe', array_column($items, 'title'));
    }

    public function test_footer_navigation(): void
    {
        $items = app(Navigation::class)->footer(null);
        $this->assertSame(['Home', 'About', 'Recipes', 'Contact', 'Privacy', 'Sign In'], array_column($items, 'title'));
    }
}
