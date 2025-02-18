<?php

use HuntRecipes\Database\SqlController;
use HuntRecipes\Endpoint\Common_Endpoint;
use HuntRecipes\Recipe;

require '../../../includes/common.php';

class Recipe_Top_Endpoint extends Common_Endpoint {

    public function __construct() {
        // $this->restrict_access();

        $method = $_SERVER['REQUEST_METHOD'];

        switch ($method) {
            case 'GET':
                $this->get_top_recipes();
                break;

            default:
                $this->method_not_allowed();
        }
    }

    public function get_top_recipes() {
        $data = array();
        $code = 400;
        $message = '';

        try {

            $conn = new SqlController();
            $data = Recipe::top_recipes($conn);
            $message = 'Got top recipes';
            $code = 200;

        } catch (Exception $e) {
            $message = $e->getMessage();
        }

        echo $this->response($data, $code, $message);
        return true;
    }
}

new Recipe_Top_Endpoint();
