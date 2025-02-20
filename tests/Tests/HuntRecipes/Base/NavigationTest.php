<?php

namespace Tests\HuntRecipes\Base;

require __DIR__ . '/../../../../includes/common.php';

use HuntRecipes\Base\Navigation;
use HuntRecipes\Database\SqlController;
use HuntRecipes\User\User;
use PHPUnit\Framework\TestCase;

class NavigationTest extends TestCase {
    private SqlController $conn;
    private User $mockUser;

    protected function setUp(): void {
        $_SERVER['REQUEST_URI'] = '/home/';

        // Start with a clean session
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        session_start();

        $this->conn = $this->createMock(SqlController::class);

        $this->mockUser = new User(0, $this->conn);
        $this->mockUser->id = 1;
        $this->mockUser->is_developer = false;
        $this->mockUser->is_chef = false;
    }

    protected function tearDown(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    public function testMainNavForGuestUser(): void {
        $nav = new Navigation();
        $mainNav = $nav->get_main_nav();

        // Basic checks
        $this->assertIsArray($mainNav);
        $this->assertNotEmpty($mainNav);

        // Check structure
        $this->assertArrayHasKey('title', $mainNav[0]);
        $this->assertArrayHasKey('a_href', $mainNav[0]);
        $this->assertArrayHasKey('is_active', $mainNav[0]);

        // Admin should not be visible for guest
        $adminItems = array_filter($mainNav, fn($item) => $item['title'] === 'Admin');
        $this->assertEmpty($adminItems);
    }

    public function testMainNavForDevUser(): void {
        // Simulate admin user session
        $this->mockUser->is_developer = true;
        $_SESSION['User'] = $this->mockUser;

        $nav = new Navigation();
        $mainNav = $nav->get_main_nav();

        // Admin should be visible
        $adminItems = array_filter($mainNav, fn($item) => $item['title'] === 'Admin');
        $this->assertNotEmpty($adminItems);

        $adminItem = current($adminItems);
        $this->assertArrayHasKey('submenu', $adminItem);
        $this->assertContains('Chef Approval', array_column($adminItem['submenu'], 'title'));
    }

    public function testUserNavForGuest(): void {
        $_SESSION['User'] = null;

        $nav = new Navigation();
        $userNav = $nav->get_user_nav();

        $this->assertIsArray($userNav);
        $this->assertCount(1, $userNav);
        $this->assertEquals('Sign in', $userNav[0]['title']);
    }

    public function testUserNavForBasicUser(): void {
        // Simulate authenticated user session
        $this->mockUser->is_developer = false;
        $this->mockUser->is_chef = false;
        $_SESSION['User'] = $this->mockUser;

        $nav = new Navigation();
        $userNav = $nav->get_user_nav();

        $this->assertIsArray($userNav);
        $this->assertCount(2, $userNav);
        $this->assertContains('My Account', array_column($userNav, 'title'));
        $this->assertContains('Favorites', array_column($userNav, 'title'));
    }

    public function testUserNavForChef(): void {
        // Simulate chef user session
        $this->mockUser->is_developer = true;
        $this->mockUser->is_chef = true;
        $_SESSION['User'] = $this->mockUser;

        $nav = new Navigation();
        $userNav = $nav->get_user_nav();

        $this->assertContains('Submit a recipe', array_column($userNav, 'title'));
    }

    public function testFooterNav(): void {
        $nav = new Navigation();
        $footerNav = $nav->get_footer_nav();

        $this->assertIsArray($footerNav);
        $this->assertNotEmpty($footerNav);

        // Basic items should always be present
        $expectedItems = ['Home', 'About', 'Recipes', 'Contact'];
        foreach ($expectedItems as $item) {
            $this->assertContains($item, array_column($footerNav, 'title'));
        }
    }
}
