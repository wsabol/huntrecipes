<?php

use HuntRecipes\Base\Page_Controller;
use HuntRecipes\Database\SqlController;
use HuntRecipes\Recipe;
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
$page_title = "Home";

// Breadcrumbs.
$breadcrumbs = array(
    array(
        'name' => $page_title,
        'link' => '/home/',
        'current_page' => true,
    ),
);

$conn = new SqlController();

// Template variables.
$page = new Page_Controller();
$context = $page->get_page_context($sess, $page_title, $breadcrumbs, [
    "top_categories" => Recipe::top_recipe_categories($conn)
]);

// Render view.
echo $twig->render('home.twig', $context);
