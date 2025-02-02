<?php

use HuntRecipes\Database\SqlController;
use HuntRecipes\Endpoint\Common_Endpoint;
use HuntRecipes\Chef;

require '../../../includes/common.php';

class Chef_Recipes_Endpoint extends Common_Endpoint {

    public function __construct() {
        // $this->restrict_access();

        $method = $_SERVER['REQUEST_METHOD'];

        switch ($method) {
            case 'GET':
                $this->get_recipes();
                break;

            default:
                $this->method_not_allowed();
        }
    }

    public function get_recipes() {
        $data = array();
        $code = 400;
        $message = '';

        try {

            $request = (object)$_GET;

            if (!$request->chef_id) {
                throw new Exception("chef_id is not set");
            }

            $conn = new SqlController();
            $chef = new Chef($request->chef_id, $conn);

            $data = $chef->get_recipes();

            $code = 200;

        } catch (Exception $e) {
            $message = $e->getMessage();
        }

        echo $this->response($data, $code, $message);
        return true;
    }
}

new Chef_Recipes_Endpoint();
