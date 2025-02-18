<?php

use HuntRecipes\Database\SqlController;
use HuntRecipes\Endpoint\Common_Endpoint;
use HuntRecipes\Measure\Measure;

require '../../../includes/common.php';

class Measure_List_Endpoint extends Common_Endpoint {

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

            $data = Measure::list();
            $message = 'Got measures';
            $code = 200;

        } catch (Exception $e) {
            $message = $e->getMessage();
        }

        echo $this->response($data, $code, $message);
        return true;
    }
}

new Measure_List_Endpoint();
