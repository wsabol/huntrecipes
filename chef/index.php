<?php

use HuntRecipes\Base\Page_Controller;
use HuntRecipes\Database\SqlController;
use HuntRecipes\Chef;
use HuntRecipes\User\User;
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


$conn = new SqlController();
$page = new Page_Controller();
$render_not_found = false;
$chef = new Chef(0, $conn);

if (isset($_GET['id'])) {

    $chef = new Chef($_GET['id'], $conn);

    if (!isset($chef->name)) {
        $render_not_found = true;
    }
}
else {
    $render_not_found = true;
}

if ($render_not_found) {
    $page_title = "Chef not found";

    $context = $page->get_page_context($sess, $page_title, array(
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
    ));

    // Render view.
    echo $twig->render('errors/chef-not-found.twig', $context);
    exit;
}

// Page title
$page_title = "Chef $chef->name";

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

$data = $chef->toObject();
$data->profile_picture = "/assets/images/users/generic_avatar.jpg";
$data->favorites = [];

if ($data->user_id > 0) {
    $user = new User($data->user_id, $conn);
    $data->profile_picture = $user->profile_picture;
    // $data->favorites = $user->
}


// Template variables.
$page = new Page_Controller();
$context = $page->get_page_context($sess, $page_title, $breadcrumbs, [
    "chef" => $data
]);

// Render view.
echo $twig->render('pages/chef.twig', $context);
