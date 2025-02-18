<?php

use HuntRecipes\Database\SqlController;
use HuntRecipes\Endpoint\Common_Endpoint;

require '../../../../includes/common.php';

class System_Ping_Endpoint extends Common_Endpoint {

    public function __construct() {
        // $this->restrict_access();

        $method = $_SERVER['REQUEST_METHOD'];

        switch ($method) {
            case 'GET':
                $this->get_info();
                break;

            default:
                $this->method_not_allowed();
        }
    }

    public function get_info() {
        $data = array();
        $code = 400;
        $message = '';

        try {

            $data['php_version'] = phpversion();

            $conn = new SqlController();
            $data['is_db_connected'] = true;
            $data['is_production'] = IS_PRODUCTION;

            $message = "Everything is ok.";

            $code = 200;

        } catch (Exception $e) {
            $message = $e->getMessage();
        }

        echo $this->response($data, $code, $message);
        return true;
    }
}

new System_Ping_Endpoint();
