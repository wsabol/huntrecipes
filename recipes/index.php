<?php

use HuntRecipes\Base\Page_Controller;
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

// todo search by ingredients
// todo ingredient search suggestions
// todo SUBMIT A RECIPE
// todo recipe EDIT

// Page title
$page_title = "Recipes";

// Breadcrumbs.
$breadcrumbs = array(
    array(
        'name' => 'Home',
        'link' => '/home/',
        'current_page' => false,
    ),
    array(
        'name' => $page_title,
        'link' => '/recipes/',
        'current_page' => true,
    ),
);

