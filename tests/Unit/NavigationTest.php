<?php

namespace Tests\Unit;

use App\Models\User;
use App\Support\Navigation;
use Tests\TestCase;

final class NavigationTest extends TestCase {
    public function testMainNavigationForGuest(): void {
        $items = app(Navigation::class)->main(null);
        $this->assertSame(['Home', 'About', 'Recipes', 'Contact'], array_column($items, 'title'));
        $this->assertArrayHasKey('is_active', $items[0]);
    }

    public function testMainNavigationForDeveloper(): void {
        $items = app(Navigation::class)->main(User::factory()->make(['is_developer' => true]));
        $admin = collect($items)->firstWhere('title', 'Admin');
        $this->assertSame('Chef Approval', $admin['submenu'][0]['title']);
    }

    public function testUserNavigationForGuest(): void {
        $items = app(Navigation::class)->user(null);
        $this->assertCount(1, $items);
        $this->assertSame('Sign in', $items[0]['title']);
    }

    public function testUserNavigationForMember(): void {
        $items = app(Navigation::class)->user(User::factory()->make());
        $this->assertSame(['Favorites', 'Account'], array_column($items, 'title'));
    }

    public function testUserNavigationForChef(): void {
        $items = app(Navigation::class)->user(User::factory()->make(['is_chef' => true]));
        $this->assertContains('Submit a recipe', array_column($items, 'title'));
    }

    public function testFooterNavigation(): void {
        $items = app(Navigation::class)->footer(null);
        $this->assertSame(['Home', 'About', 'Recipes', 'Contact', 'Privacy', 'Sign In'], array_column($items, 'title'));
    }
}
