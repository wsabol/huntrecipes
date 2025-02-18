<?php

use HuntRecipes\Base\Page_Controller;
use HuntRecipes\User\SessionController;

require "../includes/common.php";

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

$page_title = "Contact Us";

// Breadcrumbs.
$breadcrumbs = array(
    array(
        'name' => 'Home',
        'link' => '/home/',
        'current_page' => false,
    ),
    array(
        'name' => $page_title,
        'link' => '#',
        'current_page' => true,
    ),
);

$name = "";
$email = "";

if ($sess->has_user()) {
    $name = $sess->user()->name;
    $email = $sess->user()->email;
}

// Template variables.
$page = new Page_Controller();
$context = $page->get_page_context($sess, $page_title, $breadcrumbs, [
    'form_fill' => [
        'your_name' => $name,
        'your_email' => $email
    ]
]);

// Render view.
echo $twig->render('pages/contact.twig', $context);
