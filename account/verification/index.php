<?php

use HuntRecipes\Base\Page_Controller;
use HuntRecipes\Database\SqlController;
use HuntRecipes\User\EmailVerification;
use HuntRecipes\User\SessionController;
use HuntRecipes\User\User;

require "../../includes/common.php";

$sess = new SessionController();
$sess->validate();

// Set up Twig templating.
$loader = new \Twig\Loader\FilesystemLoader(RECIPES_ROOT . '/views');
$twig = new \Twig\Environment(
    $loader,
    array(
        'debug' => false,
    )
);

$page_title = "Verification";
$context = [
    'alert' => '',
    'heading' => '',
    'message' => ''
];

// Breadcrumbs.
$breadcrumbs = array(
    array(
        'name' => 'Home',
        'link' => '/home/',
        'current_page' => false,
    ),
    array(
        'name' => 'My Account',
        'link' => '/account/',
        'current_page' => false,
    ),
    array(
        'name' => $page_title,
        'link' => '#',
        'current_page' => true,
    ),
);

$page = new Page_Controller();
$request = (object)$_GET;
$stoken = (string)@$request->stoken;

try {

    if (empty($stoken)) {
        $context['alert'] = 'warning';
        $context['heading'] = "How did you get here?";
        throw new Exception("If you feel you were brought here by mistake, please contact us and we'll figure out what happened.");
    }

    $conn = new SqlController();
    $ev = EmailVerification::from_secure_token($stoken, $conn);

    if ($ev === false) {
        $context['alert'] = 'info';
        $context['heading'] = "Hmm... Something is wrong with your link";
        throw new Exception("Sign in to your account and try sending a new verification.");
    }

    if ($ev->is_used) {
        $context['alert'] = 'warning';
        $context['heading'] = "This link has already been verified";
        throw new Exception("");
    }

    if ($ev->is_expired()) {
        $context['alert'] = 'error';
        $context['heading'] = "This link has expired";
        throw new Exception("Sign in to your account and send a new verification.");
    }

    $ev->is_used = true;
    $ev->save_to_db();

    $user = new User($ev->user_id, $conn);
    $user->is_email_verified = true;
    $user->save_to_db();

    if ($sess->has_user()) {
        if ($sess->user()->id === $user->id) {
            $sess->start();
            $sess->set_user($user);
            $sess->close();
        }
    }

    $context['alert'] = 'success';
    $context['heading'] = "Your account is verified!";
    $context['message'] = "Thank you for verifying your account. Happy Cooking!";

}
catch (Exception $e) {
    $context['alert'] = $context['alert'] ?? 'warning';
    $context['heading'] = $context['heading'] ?? 'Something went wrong';
    $context['message'] = $e->getMessage();
}


// Template variables.
$page = new Page_Controller();
$context = $page->get_page_context($sess, $page_title, $breadcrumbs, $context);

// Render view.
echo $twig->render('pages/account-verify-confirm.twig', $context);
