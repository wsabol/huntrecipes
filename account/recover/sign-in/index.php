<?php

use HuntRecipes\Base\Page_Controller;
use HuntRecipes\Database\SqlController;
use HuntRecipes\User\SessionController;
use HuntRecipes\User\User;
use HuntRecipes\User\UserOneTimePassword;

require "../../../includes/common.php";

$sess = new SessionController();
$sess->validate();

if ($sess->has_user()) {
    http_response_code(302);
    header('Location: /home/');
    exit();
}

// Set up Twig templating.
$loader = new \Twig\Loader\FilesystemLoader(RECIPES_ROOT . '/views');
$twig = new \Twig\Environment(
    $loader,
    array(
        'debug' => false,
    )
);

// test input
$request = (object)$_GET;
$render_error = false;
$message = "";

try {

    if (!isset($request->email)) {
        $sess->page_redirect("/welcome/");
    }
    if (!isset($request->otp)) {
        $sess->page_redirect("/welcome/");
    }

    $conn = new SqlController();
    $user = User::create_from_email($conn, $request->email);

    if (!$user) {
        throw new Exception("Account does not exist: $request->email");
    }
    if (!$user->is_enabled()) {
        throw new Exception("This account is not enabled (id:$request->user_id)");
    }

    $otp = new UserOneTimePassword($request->otp, $conn);
    if (@$otp->user_id !== $user->id) {
        throw new Exception("This request is invalid");
    }
    if (!$otp->is_enabled) {
        throw new Exception("This link has expired. Go back and get a new code.");
    }
    if ($otp->is_used()) {
        throw new Exception("This one-time password has already used");
    }
    if ($otp->is_expired()) {
        throw new Exception("This one-time password has expired");
    }

    $message = "Sign in using the code we sent to your email";
}
catch (Exception $e) {
    $render_error = true;
    $message = $e->getMessage();
}

$page_title = "One-time Password Sign In";

// Breadcrumbs.
$breadcrumbs = array(
    array(
        'name' => 'Sign In',
        'link' => '/account/sign-in/',
        'current_page' => false,
    ),
    array(
        'name' => "Account Recovery",
        'link' => '#',
        'current_page' => false,
    ),
    array(
        'name' => $page_title,
        'link' => '#',
        'current_page' => true,
    ),
);

// Template variables.
$page = new Page_Controller();
$context = $page->get_page_context($sess, $page_title, $breadcrumbs, [
    'email' => $request->email,
    'render_error' => $render_error,
    'message' => $message,
]);

// Render view.
echo $twig->render('pages/account-recovery-sign-in.twig', $context);
