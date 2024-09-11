<?php

use HuntRecipes\Base\Page_Controller;
use HuntRecipes\Database\SqlController;
use HuntRecipes\Recipe;
use HuntRecipes\User\SessionController;

require_once("../../includes/common.php");

$sess = new SessionController();

// Set up Twig templating.
$loader = new \Twig\Loader\FilesystemLoader(RECIPES_ROOT . '/views');
$twig = new \Twig\Environment(
    $loader,
    array(
        'debug' => false,
    )
);

$conn = new SqlController();
$page = new Page_Controller();
$render_not_found = false;
$recipe = new Recipe(0, $conn);

if (isset($_GET['id'])) {

    $recipe = new Recipe($_GET['id'], $conn);

    if (!isset($recipe->title)) {
        $render_not_found = true;
    }
}
else {
    $render_not_found = true;
}

if ($render_not_found) {
    $page_title = "Recipe not found";

    $context = $page->get_page_context($sess, $page_title, array(
        array(
            'name' => 'Home',
            'link' => '/home/',
            'current_page' => false,
        ),
        array(
            'name' => 'Recipes',
            'link' => '/recipes/',
            'current_page' => false,
        ),
        array(
            'name' => $page_title,
            'link' => '#',
            'current_page' => true,
        ),
    ));

    // Render view.
    echo $twig->render('recipes/recipe-not-found.twig', $context);
    exit;
}

// Page title
$page_title = $recipe->title;

// Breadcrumbs.
$breadcrumbs = array(
    array(
        'name' => 'Home',
        'link' => '/home/',
        'current_page' => false,
    ),
    array(
        'name' => 'Recipes',
        'link' => '/recipes/',
        'current_page' => false,
    ),
    array(
        'name' => $page_title,
        'link' => $recipe->get_link(),
        'current_page' => true,
    ),
);

// Template variables.
$data = $recipe->toObject();
$data->recipe_type = $recipe->get_report_type();
$data->course = $recipe->get_course();
$data->cuisine = $recipe->get_cuisine();
$data->chef = $recipe->get_chef();
$data->ingredients = $recipe->get_ingredients();
$data->instructions = $recipe->get_instructions();
$data->is_liked = false;
$data->likes_count = $recipe->get_likes_count();
$data->link = $recipe->get_link();
$data->ingredient_columns = [];
$data->liked_by = $recipe->get_users_who_liked_this();

if (count($data->ingredients) < 12) {
    $data->ingredient_columns[] = [
        'items' => $data->ingredients
    ];
    $data->ingredient_columns[] = [
        'items' => []
    ];
}
else {
    $breakpoint = ceil(2 * count($data->ingredients) / 3);
    $data->ingredient_columns[] = [
        'items' => array_slice($data->ingredients, 0, $breakpoint)
    ];
    $data->ingredient_columns[] = [
        'items' => array_slice($data->ingredients, $breakpoint)
    ];
}

$data->ingredient_col_num = 12 / count($data->ingredient_columns);

if ($sess->has_user()) {
    $data->is_liked = $recipe->is_liked($sess->user()->id);
}

$context = $page->get_page_context($sess, $page_title, $breadcrumbs, [
    'recipe' => (array)$data
]);

// Render view.
echo $twig->render('recipes/recipe-single.twig', $context);
