<?php

use HuntRecipes\Base\Page_Controller;
use HuntRecipes\Database\SqlController;
use HuntRecipes\User\ChefApplication;
use HuntRecipes\User\ChefApplicationStatus;
use HuntRecipes\User\SessionController;

require_once("../../includes/common.php");

$sess = new SessionController();
$sess->require_valid_user();

if (!$sess->user()->is_developer) {
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

// Page title
$page_title = "Chef Approval";

// Breadcrumbs.
$breadcrumbs = array(
    array(
        'name' => 'Home',
        'link' => '/home/',
        'current_page' => false,
    ),
    array(
        'name' => 'Admin',
        'link' => '/admin/',
        'current_page' => false,
    ),
    array(
        'name' => $page_title,
        'link' => '/admin/chef-approval/',
        'current_page' => true,
    ),
);

$conn = new SqlController();
$pending_apps = ChefApplication::list($conn, [
    'chef_application_status_id' => ChefApplicationStatus::PENDING
]);

// Template variables.
$page = new Page_Controller();
$context = $page->get_page_context($sess, $page_title, $breadcrumbs, [
    'pending_apps' => $pending_apps
]);

// Render view.
echo $twig->render('pages/admin-chef-approval.twig', $context);
