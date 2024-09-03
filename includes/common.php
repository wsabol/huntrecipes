<?php

if (!defined('RECIPES_INCLUDES')) {
    /** @var string $RECIPES_INCLUDES Absolute Path to Project Includes */
    define('RECIPES_INCLUDES', __DIR__);
}

if (!defined('RECIPES_ROOT')) {
    /** @var string $JRSPAY_ROOT Absolute Path to Project Root */
    define('RECIPES_ROOT', realpath(RECIPES_INCLUDES . "/.."));
}

// require functions
require_once RECIPES_INCLUDES . "/utils.php";

// require composer
require_once RECIPES_ROOT . "/vendor/autoload.php";
require_once RECIPES_INCLUDES . '/HuntRecipes/Autoloader.php';

/* load environment vars */
$dotenv = Dotenv\Dotenv::createImmutable(RECIPES_ROOT);
$dotenv->load();
$dotenv->required(['DB_HOST', 'DB_USERNAME', 'DB_PASSWORD']);
unset($dotenv);

if (!defined('IS_PRODUCTION')) {
    /** @var bool $IS_PRODUCTION Whether on production server */
    define("IS_PRODUCTION", filter_var($_ENV['PRODUCTION'], FILTER_VALIDATE_BOOL));
}

error_reporting(E_ALL);
ini_set("display_errors", IS_PRODUCTION ? 0 : 1);
