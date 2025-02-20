<?php

namespace HuntRecipes\Base;

use HuntRecipes\Exception\HuntRecipesException;
use PHPMailer\PHPMailer\PHPMailer;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class Email_Controller {
    private const VIEW_DIR = RECIPES_ROOT . "/views";

    private PHPMailer $mailer;
    private string $view;
    private string $subject;
    private string $body;

    public function __construct() {
        $this->view = "emails/email-base.twig";
        $this->subject = '';
        $this->body = '';

        // Create a new PHPMailer instance
        $this->mailer = new PHPMailer();
        // Tell PHPMailer to use SMTP
        $this->mailer->isSMTP();

        // Enable SMTP debugging
        // 0 = off (for production use)
        // 1 = client messages
        // 2 = client and server messages
        $this->mailer->SMTPDebug = 0;
        // Set the hostname of the mail server
        $this->mailer->Host = $_ENV['MAIL_HOST'];
        // Set the SMTP port number - likely to be 25, 465 or 587
        $this->mailer->Port = $_ENV['MAIL_PORT'];
        // Whether to use SMTP authentication
        $this->mailer->SMTPAuth = true;
        $this->mailer->SMTPSecure = 'ssl';
        // Username to use for SMTP authentication
        $this->mailer->Username = $_ENV['MAIL_USERNAME'];
        // Password to use for SMTP authentication
        $this->mailer->Password = $_ENV['MAIL_PASSWORD'];
        // set from
        $this->mailer->setFrom($_ENV['MAIL_USERNAME']);
        // html body
        $this->mailer->IsHTML(true);
    }

    public function get_subject(): string {
        return $this->subject;
    }

    public function set_view(string $view): void {
        if (str_starts_with($view, "/")) {
            $view = substr($view, 1);
        }

        if (!file_exists(self::VIEW_DIR . "/$view")) {
            throw new HuntRecipesException("view doe note exist: /$view");
        }

        $this->view = $view;
    }

    public function set_subject(string $subject): void {
        $this->subject = $subject;
        $this->mailer->Subject = $subject;
    }

    public function add_address(string $address): void {
        // Set who the message is to be sent to
        $this->mailer->addAddress($address);
    }

    public function add_reply_to(string $address): void {
        // Set who the message is to be sent to
        $this->mailer->addReplyTo($address);
    }

    public function set_message_context(array $context): void {

        // Set up Twig templating.
        $loader = new FilesystemLoader(self::VIEW_DIR);
        $twig = new Environment(
            $loader,
            array(
                'debug' => false,
            )
        );

        $this->body = $twig->render($this->view, $context);
        $this->mailer->msgHTML($this->body);
    }

    public function send(): void {

        if (empty(@$this->subject)) {
            throw new HuntRecipesException("subject is not set");
        }

        if (empty(@$this->body)) {
            throw new HuntRecipesException("message body is not set");
        }

        //send the message, check for errors
        if (!$this->mailer->send()) {
            throw new HuntRecipesException("Message failed to send: " . $this->mailer->ErrorInfo);
        }
    }
}
