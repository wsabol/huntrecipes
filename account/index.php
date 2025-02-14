<?php

use HuntRecipes\Base\Page_Controller;
use HuntRecipes\Chef;
use HuntRecipes\Database\SqlController;
use HuntRecipes\User\ChefApplication;
use HuntRecipes\User\ChefRelation;
use HuntRecipes\User\SessionController;
use HuntRecipes\User\User;

require_once("../includes/common.php");

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

// Page title
$page_title = "My Account";

// Breadcrumbs.
$breadcrumbs = array(
    array(
        'name' => 'Home',
        'link' => '/home/',
        'current_page' => false,
    ),
    array(
        'name' => $page_title,
        'link' => '/account/',
        'current_page' => true,
    ),
);

// todo chef approval

$conn = new SqlController();

// set new user just in case
$user_controller = new User($sess->user()->id, $conn);
$sess->set_user($user_controller);

// get chef
$chef = false;
if ($sess->user()->is_chef) {
    $chef = new Chef($sess->user()->chef_id, $conn);
}

// get user
$user = $sess->user()->toObject();
$user->has_open_email_verification = $sess->user()->has_open_email_verification();

// get chef application
$chef_app = false;
if ($user->chef_application_id > 0) {
    $app = new ChefApplication($sess->user()->chef_application_id, $conn);
    if (!$app->is_deleted) {
        $chef_app = $app->toObject();
        $chef_app->relationship_name = ChefRelation::get_name($chef_app->relationship);
    }
}

// get relationships
$relationships = ChefRelation::list();

// Template variables.
$page = new Page_Controller();
$context = $page->get_page_context($sess, $page_title, $breadcrumbs, [
    'user' => $user,
    'chef' => $chef,
    'chef_app' => $chef_app,
    'relationships' => $relationships,
    'goto' => @$_GET['goto'] ?? ""
]);

// Render view.
echo $twig->render('pages/account.twig', $context);
