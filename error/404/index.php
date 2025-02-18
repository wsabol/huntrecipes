<?php

use HuntRecipes\Base\Page_Controller;
use HuntRecipes\User\SessionController;

require_once("../../includes/common.php");

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
$page_title = "Page not found";

// Breadcrumbs.
$breadcrumbs = array(
    array(
        'name' => 'Home',
        'link' => '/home/',
        'current_page' => false,
    ),
    array(
        'name' => $page_title,
        'link' => '/hrm/',
        'current_page' => true,
    ),
);

// Template variables.
$page = new Page_Controller();
$context = $page->get_page_context($sess, $page_title, $breadcrumbs);

// Render view.
echo $twig->render('errors/404.twig', $context);
