<?php

use HuntRecipes\Base\Page_Controller;
use HuntRecipes\Database\SqlController;
use HuntRecipes\User\ResetPasswordAuth;
use HuntRecipes\User\SessionController;
use HuntRecipes\User\User;

require "../../../includes/common.php";

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

$page_title = "Reset Password";
$context = [
    'alert' => '',
    'heading' => '',
    'message' => '',
    'is_valid' => false
];

// Breadcrumbs.
$breadcrumbs = array(
    array(
        'name' => 'Home',
        'link' => '/home/',
        'current_page' => false,
    ),
    array(
        'name' => 'Sign In',
        'link' => '/account/sign-in/',
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
$hashed_token = (string)@$request->hash;

try {

    if (empty($hashed_token)) {
        $context['alert'] = 'error';
        $context['heading'] = "How did you get here?";
        throw new Exception("If you feel you were brought here by mistake, please contact us and we'll figure out what happened.");
    }

    $conn = new SqlController();
    $rp = ResetPasswordAuth::from_hashed_token($hashed_token, $conn);

    if ($rp === false) {
        $context['alert'] = 'error';
        $context['heading'] = "Hmm... Something is wrong with your link";
        throw new Exception("Sign in to your account and try sending a new request.");
    }

    if ($rp->is_used) {
        $context['alert'] = 'error';
        $context['heading'] = "This link was already used";
        throw new Exception("Sign in to your account and send a new request.");
    }

    if ($rp->is_expired()) {
        $context['alert'] = 'error';
        $context['heading'] = "This link has expired";
        throw new Exception("Sign in to your account and send a new request.");
    }

    if ($rp->user_id != $sess->user()->id) {
        $context['alert'] = 'error';
        $context['heading'] = "Hmm... Something is wrong";
        throw new Exception("Sign in to your account and try sending a new request.");
    }

    $rp->is_used = true;
    $rp->save_to_db();

    $context['alert'] = '';
    $context['heading'] = "Reset your password";
    $context['message'] = "Please enter your new password below.";
    $context['is_valid'] = true;
    $context['user_id'] = $rp->user_id;

}
catch (Exception $e) {
    $context['is_valid'] = false;
    $context['alert'] = $context['alert'] ?? 'error';
    $context['heading'] = $context['heading'] ?? 'Something went wrong';
    $context['message'] = $e->getMessage();
}


// Template variables.
$page = new Page_Controller();
$context = $page->get_page_context($sess, $page_title, $breadcrumbs, $context);

// Render view.
echo $twig->render('pages/account-reset-password.twig', $context);
