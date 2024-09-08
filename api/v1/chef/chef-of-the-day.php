<?php

use HuntRecipes\Chef;
use HuntRecipes\Database\SqlController;
use HuntRecipes\Endpoint\Common_Endpoint;

require '../../../includes/common.php';

class Recipe_ChefOfTheDay_Endpoint extends Common_Endpoint {

    public function __construct() {
        // $this->restrict_access();

        $method = $_SERVER['REQUEST_METHOD'];

        switch ($method) {
            case 'GET':
                $this->get_cotd();
                break;

            default:
                $this->method_not_allowed();
        }
    }

    public function get_cotd() {
        $data = array();
        $code = 400;
        $message = '';

        try {

            $conn = new SqlController();
            $recipe = Chef::chef_of_The_day($conn);

            $data = $recipe->toObject();

            $message = 'Got chef of the day';
            $code = 200;

        } catch (Exception $e) {
            $message = $e->getMessage();
        }

        echo $this->response($data, $code, $message);
        return true;
    }
}

new Recipe_ChefOfTheDay_Endpoint();
