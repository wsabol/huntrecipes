<?php

use HuntRecipes\Base\Page_Controller;
use HuntRecipes\Database\SqlController;
use HuntRecipes\User\SessionController;
use HuntRecipes\User\User;

require "../../includes/common.php";

$sess = new SessionController();
$sess->validate();

if ($sess->has_user()) {
    $sess->page_redirect('/home/');
}

$request = (object)$_GET;
if (!isset($request->email)) {
    $sess->page_redirect('/home/');
}
$email = $request->email;

// Set up Twig templating.
$loader = new \Twig\Loader\FilesystemLoader(RECIPES_ROOT . '/views');
$twig = new \Twig\Environment(
    $loader,
    array(
        'debug' => false,
    )
);

$page_title = "Account Recovery";

// Breadcrumbs.
$breadcrumbs = array(
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
$context = $page->get_page_context($sess, $page_title, $breadcrumbs);

// test input
$conn = new SqlController();
$user = User::create_from_email($conn, $email);
$render_error = false;

if (!$user) {
    $render_error = true;
} elseif (!$user->is_enabled()) {
    $render_error = true;
}

if ($render_error) {
    echo $twig->render('errors/account-recovery-error.twig', $context);
    exit;
}

// Render view.
$context['user'] = (array)($user->toObject());
echo $twig->render('pages/account-recovery.twig', $context);
