<?php

use HuntRecipes\Chef;
use HuntRecipes\Database\SqlController;
use HuntRecipes\Recipe;

require __DIR__ . '/../includes/common.php';

class Recipe_Chef_OfTheDay_Automation {

    public function __construct() {
        $exit_code = 1;

        try {
            $date = new DateTimeImmutable();
            $conn = new SqlController();

            Recipe::set_new_recipe_of_the_day($date, $conn);
            Chef::set_new_chef_of_the_day($date, $conn);

            echo "Success \n";
            $exit_code = 0;
        }
        catch (Throwable $e) {
            $code = $e->getCode() !== 0 ? $e->getCode() : -1;

            // print message
            $eTpl1 = get_class($e) . "(%d): %s\n";
            echo sprintf($eTpl1, $code, $e->getMessage());

            // print details
            $eTpl2 = "File: %s(Line %d)\nTrace:\n%s\n";
            echo sprintf($eTpl2, $e->getFile(), $e->getLine(), $e->getTraceAsString());
        }

        exit($exit_code);
    }
}

new Recipe_Chef_OfTheDay_Automation();
