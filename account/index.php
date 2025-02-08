<?php

use HuntRecipes\Base\Page_Controller;
use HuntRecipes\Chef;
use HuntRecipes\Database\SqlController;
use HuntRecipes\User\SessionController;
use HuntRecipes\User\User;

require_once("../includes/common.php");

$sess = new SessionController();
$sess->require_valid_user();

// Set up Twig templating.
$loader = new \Twig\Loader\FilesystemLoader(RECIPES_ROOT . '/views');
$twig = new \Twig\Environment(
    $loader,
    array(
        'debug' => false,
    )
);

// Page title
$page_title = "My Account";

// Breadcrumbs.
$breadcrumbs = array(
    array(
        'name' => 'Home',
        'link' => '/home/',
        'current_page' => false,
    ),
    array(
        'name' => $page_title,
        'link' => '/account/',
        'current_page' => true,
    ),
);

// todo reset password
// todo Chef application
// todo dev utilities

$conn = new SqlController();

// set new user just in case
$user_controller = new User($sess->user()->id, $conn);
$sess->set_user($user_controller);

$chef = false;

if ($sess->user()->is_chef) {
    $chef = Chef::from_user($sess->user()->id, $conn);
}

$user = $sess->user()->toObject();
$user->has_open_email_verification = $sess->user()->has_open_email_verification();

// Template variables.
$page = new Page_Controller();
$context = $page->get_page_context($sess, $page_title, $breadcrumbs, [
    'user' => $user,
    'chef' => $chef,
    'goto' => @$_GET['goto'] ?? ""
]);

// Render view.
echo $twig->render('pages/account.twig', $context);
