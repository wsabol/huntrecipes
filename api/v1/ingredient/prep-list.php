<?php

use HuntRecipes\Database\SqlController;
use HuntRecipes\Endpoint\Common_Endpoint;
use HuntRecipes\Ingredient;

require '../../../includes/common.php';

class Ingredients_PrepList_Endpoint extends Common_Endpoint {

    public function __construct() {
        // $this->restrict_access();

        $method = $_SERVER['REQUEST_METHOD'];

        switch ($method) {
            case 'GET':
                $this->list();
                break;

            default:
                $this->method_not_allowed();
        }
    }

    public function list() {
        $data = array();
        $code = 400;
        $message = '';

        try {

            $data = Ingredient::prep_list(new SqlController(), []);
            $message = 'Got ingredient prep list';
            $code = 200;

        } catch (Exception $e) {
            $message = $e->getMessage();
        }

        echo $this->response($data, $code, $message);
        return true;
    }
}

new Ingredients_PrepList_Endpoint();
