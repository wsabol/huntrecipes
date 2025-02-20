<?php

namespace Tests\HuntRecipes\Base;

require __DIR__ . '/../../../../includes/common.php';

use HuntRecipes\Base\Page_Controller;
use HuntRecipes\User\SessionController;
use HuntRecipes\User\User;
use PHPUnit\Framework\TestCase;

class PageControllerTest extends TestCase {
    private Page_Controller $pageController;
    private SessionController $mockSession;

    protected function setUp(): void {
        $this->pageController = new Page_Controller();
        $this->mockSession = $this->createMock(SessionController::class);
    }

    public function testGetPageContextBasic(): void {
        $this->mockSession->method('has_user')->willReturn(false);

        $context = $this->pageController->get_page_context(
            $this->mockSession,
            'Test Page',
            ['Home', 'Test']
        );

        $this->assertArrayHasKey('page_title', $context);
        $this->assertArrayHasKey('breadcrumbs', $context);
        $this->assertArrayHasKey('main_nav', $context);
        $this->assertArrayHasKey('user_nav', $context);
        $this->assertArrayHasKey('footer_nav', $context);
        $this->assertArrayHasKey('current_year', $context);

        $this->assertEquals('Test Page', $context['page_title']);
        $this->assertEquals(['Home', 'Test'], $context['breadcrumbs']);
        $this->assertEquals(0, $context['current_user_id']);
    }

    public function testGetPageContextWithUser(): void {
        $mockUser = $this->createMock(User::class);
        $mockUser->id = 123;

        $this->mockSession->method('has_user')->willReturn(true);
        $this->mockSession->method('user')->willReturn($mockUser);

        $context = $this->pageController->get_page_context(
            $this->mockSession,
            'Test Page',
            ['Home', 'Test']
        );

        $this->assertEquals(123, $context['current_user_id']);
    }

    public function testGetPageContextWithAdditionalData(): void {
        $this->mockSession->method('has_user')->willReturn(false);

        $additional = [
            'custom_key' => 'custom_value',
            'another_key' => [1, 2, 3]
        ];

        $context = $this->pageController->get_page_context(
            $this->mockSession,
            'Test Page',
            ['Home', 'Test'],
            $additional
        );

        $this->assertArrayHasKey('custom_key', $context);
        $this->assertArrayHasKey('another_key', $context);
        $this->assertEquals('custom_value', $context['custom_key']);
        $this->assertEquals([1, 2, 3], $context['another_key']);
    }
}
