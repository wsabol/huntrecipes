<?php

use HuntRecipes\Base\Page_Controller;
use HuntRecipes\Database\SqlController;
use HuntRecipes\Recipe;
use HuntRecipes\User\SessionController;

require_once("../../../includes/common.php");

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
        'name' => 'Back to Recipe',
        'link' => '/recipes/recipe/?id=' . $_GET['id'],
        'current_page' => false,
    ),
    array(
        'name' => $page_title,
        'link' => '#',
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
$data->i_am_the_chef = false;
$data->ingredient_columns = [];
$data->liked_by = $recipe->get_users_who_liked_this();
$data->children = [];

$children = $recipe->get_child_recipes();
foreach ($children as $child) {
    $child_obj = $child->toObject();
    $child_obj->ingredients = $child->get_ingredients();
    $child_obj->instructions = $child->get_instructions();

    $data->children[] = $child_obj;
}

$data->ingredient_columns = Recipe::organize_ingredients_into_columns(
    $data->ingredients,
    @$data->children[0]->ingredients ?? [],
    @$data->children[1]->ingredients ?? []
);

$data->ingredient_col_num = 12 / count($data->ingredient_columns);

$context = $page->get_page_context($sess, $page_title, $breadcrumbs, [
    'recipe' => (array)$data
]);

// Render view.
echo $twig->render('recipes/recipe-print.twig', $context);
