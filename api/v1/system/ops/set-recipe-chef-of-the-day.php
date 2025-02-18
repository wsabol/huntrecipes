<?php

use HuntRecipes\Chef;
use HuntRecipes\Database\SqlController;
use HuntRecipes\Endpoint\Common_Endpoint;
use HuntRecipes\Recipe;

require __DIR__ . '/../../../../includes/common.php';

class Recipe_Chef_OfTheDay_Endpoint extends Common_Endpoint {

    public function __construct() {
        // $this->restrict_access();

        $method = $_SERVER['REQUEST_METHOD'];

        switch ($method) {
            case 'POST':
                $this->set_stuff_of_the_day();
                break;

            default:
                $this->method_not_allowed();
        }
    }

    public function set_stuff_of_the_day(): true {
        $data = array();
        $code = 400;
        $message = '';

        try {
            $date = new DateTimeImmutable();
            $conn = new SqlController();

            Recipe::set_new_recipe_of_the_day($date, $conn);
            Chef::set_new_chef_of_the_day($date, $conn);

            $message = "Success";
            $code = 200;

        } catch (Exception $e) {
            $message = $e->getMessage();
        }

        echo $this->response($data, $code, $message);
        return true;
    }
}

new Recipe_Chef_OfTheDay_Endpoint();
