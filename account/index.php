<?php

use HuntRecipes\Base\Page_Controller;
use HuntRecipes\Chef;
use HuntRecipes\Database\SqlController;
use HuntRecipes\User\SessionController;

require_once("../includes/common.php");

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

// Page title
$page_title = "My Account";

// Breadcrumbs.
$breadcrumbs = array(
    array(
        'name' => 'Home',
        'link' => '/home/',
        'current_page' => true,
    ),
    array(
        'name' => $page_title,
        'link' => '/account/',
        'current_page' => true,
    ),
);

$conn = new SqlController();
$chef = false;

if ($sess->user()->is_chef) {
    $chef = Chef::from_user($sess->user()->id, $conn);
}

// Template variables.
$page = new Page_Controller();
$context = $page->get_page_context($sess, $page_title, $breadcrumbs, [
    'user' => $sess->user(),
    'chef' => $chef,
    'goto' => @$_GET['goto'] ?? ""
]);

// Render view.
echo $twig->render('account.twig', $context);
