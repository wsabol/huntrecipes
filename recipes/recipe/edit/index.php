<?php

use HuntRecipes\Base\Page_Controller;
use HuntRecipes\Chef;
use HuntRecipes\Database\SqlController;
use HuntRecipes\Recipe;
use HuntRecipes\User\SessionController;

require_once("../../../includes/common.php");

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

// access denied := user is not a chef, recipes is not my recipe

$conn = new SqlController();
$page = new Page_Controller();
$render_access_denied = false;
$render_not_found = false;
$recipe = new Recipe(0, $conn);

try {

    if (!isset($_GET['id'])) {
        $render_not_found = true;
        throw new Exception();
    }

    if (!$sess->user()->is_chef) {
        $render_access_denied = true;
        throw new Exception();
    }

    if ($_GET['id'] === 'new') {
        // new recipe
        throw new Exception();
    }

    $recipe = new Recipe((int)$_GET['id'], $conn);

    if (!isset($recipe->title)) {
        $render_not_found = true;
        throw new Exception();
    }

    $chef = new Chef($recipe->chef_id, $conn);
    if ($chef->user_id !== $sess->user()->id) {
        $render_access_denied = true;
        throw new Exception();
    }

}
catch (Exception $e) {}

if ($render_access_denied) {
    $page_title = "Access denied";

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
    echo $twig->render('recipes/recipe-access-denied.twig', $context);
    exit;
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
$page_title = $recipe->title ?? "New Recipe";

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
        'name' => isset($recipe->title) ? 'Edit' : 'Submit',
        'link' => '#',
        'current_page' => false,
    ),
    array(
        'name' => $page_title,
        'link' => $recipe->get_link(),
        'current_page' => true,
    ),
);

// Template variables.
if (isset($recipe->title)) {
    $data = $recipe->toObject();
}
else {
    $data = [];
}

$context = $page->get_page_context($sess, $page_title, $breadcrumbs, [
    'recipe' => (array)$data
]);

// Render view.
echo $twig->render('recipes/recipe-edit.twig', $context);
