<?php

use HuntRecipes\Base\Page_Controller;
use HuntRecipes\User\SessionController;

require "../../includes/common.php";

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

$page_title = "Account Identification";

// Breadcrumbs.
$breadcrumbs = array(
    array(
        'name' => $page_title,
        'link' => '#',
        'current_page' => true,
    ),
);

// Template variables.
$page = new Page_Controller();
$context = $page->get_page_context($sess, $page_title, $breadcrumbs, [
    'email' => @$_GET['email']
]);

// Render view.
echo $twig->render('pages/account-identify.twig', $context);
