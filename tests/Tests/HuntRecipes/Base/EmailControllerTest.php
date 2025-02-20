<?php

namespace Tests\HuntRecipes\Base;

require __DIR__ . '/../../../../includes/common.php';

use HuntRecipes\Base\Email_Controller;
use HuntRecipes\Exception\HuntRecipesException;
use PHPUnit\Framework\TestCase;

class EmailControllerTest extends TestCase {
    private Email_Controller $emailController;

    protected function setUp(): void {
        // Set required environment variables for testing
        $_ENV['MAIL_HOST'] = 'smtp.test.com';
        $_ENV['MAIL_PORT'] = '465';
        $_ENV['MAIL_USERNAME'] = 'test@test.com';
        $_ENV['MAIL_PASSWORD'] = 'password';

        $this->emailController = new Email_Controller();
    }

    public function testSetAndGetSubject(): void {
        $subject = 'Test Email Subject';
        $this->emailController->set_subject($subject);
        $this->assertEquals($subject, $this->emailController->get_subject());
    }

    public function testSetInvalidView(): void {
        $this->expectException(HuntRecipesException::class);
        $this->emailController->set_view('/nonexistent-view.twig');
    }

    public function testSendWithoutSubject(): void {
        $this->expectException(HuntRecipesException::class);
        $this->expectExceptionMessage('subject is not set');
        $this->emailController->send();
    }

    public function testSendWithoutBody(): void {
        $this->emailController->set_subject('Test Subject');
        $this->expectException(HuntRecipesException::class);
        $this->expectExceptionMessage('message body is not set');
        $this->emailController->send();
    }
}
