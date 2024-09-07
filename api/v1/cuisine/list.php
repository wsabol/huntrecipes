<?php

use HuntRecipes\Database\SqlController;
use HuntRecipes\Endpoint\Common_Endpoint;
use HuntRecipes\Cuisine;

require '../../../includes/common.php';

class Cuisine_List_Endpoint extends Common_Endpoint {

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

            $conn = new SqlController();
            $data = Cuisine::list($conn, []);
            $code = 200;

        } catch (Exception $e) {
            $message = $e->getMessage();
        }

        echo $this->response($data, $code, $message);
        return true;
    }
}

new Cuisine_List_Endpoint();
