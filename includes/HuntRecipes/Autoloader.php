<?php

namespace HuntRecipes;

Autoloader::register();

/**
 * Autoloader
 *
 * @package     HuntRecipes
 */
class Autoloader {
    const PREFIX = 'HuntRecipes';

    /**
     * Register the Autoloader with SPL
     *
     * @return void
     */
    public static function register(): void {
        spl_autoload_register(array(new self, 'load'));
    }

    /**
     * Autoloader
     *
     * @param string $class
     * @return bool
     */
    public static function load(string $class): bool {

        if (class_exists($class, false)) {
            // Class is already loaded
            return false;
        }

        if (!str_starts_with($class, self::PREFIX)) {
            // not in this package
            return false;
        }

        // replace \ with OS defined directory separator
        $file = str_replace("\\", DIRECTORY_SEPARATOR, $class);
        // combine relative file path with includes directory to get the full include path
        $file = implode(DIRECTORY_SEPARATOR, [RECIPES_INCLUDES, $file]) . ".php";
        // resolve absolute file path
        $file = realpath($file);

        if (!file_exists($file) || !is_readable($file)) {
            // Can't load
            return false;
        }

        require_once $file;
        return true;
    }
}
