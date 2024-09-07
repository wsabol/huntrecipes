<?php

use HuntRecipes\Base\Page_Controller;
use HuntRecipes\User\SessionController;

require_once("../includes/common.php");

$sess = new SessionController();

// Set up Twig templating.
$loader = new \Twig\Loader\FilesystemLoader(RECIPES_ROOT . '/views');
$twig = new \Twig\Environment(
    $loader,
    array(
        'debug' => false,
    )
);

// Page title
$page_title = "Welcome";

// Breadcrumbs.
$breadcrumbs = array(
    array(
        'name' => $page_title,
        'link' => '/welcome/',
        'current_page' => true,
    ),
);

// Template variables.
$page = new Page_Controller();
$context = $page->get_page_context($sess, $page_title, $breadcrumbs);

// Render view.
echo $twig->render('welcome.twig', $context);
