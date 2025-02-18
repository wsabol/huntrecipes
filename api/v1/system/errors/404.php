<?php

use HuntRecipes\Endpoint\Common_Endpoint;

require '../../../../includes/common.php';

class System_Error_404_Endpoint extends Common_Endpoint {

    public function __construct() {
        // $this->restrict_access();

        $method = $_SERVER['REQUEST_METHOD'];

        switch ($method) {
            case 'GET':
                $this->get_response();
                break;

            default:
                $this->method_not_allowed();
        }
    }

    public function get_response() {
        $data = (object)$_GET;
        $code = 404;
        $message = 'The requested URI is not found';

        if (isset($data->request_uri)) {
            $message .= ": {$data->request_uri}";
        }

        echo $this->response($data, $code, $message);
        return true;
    }
}

new System_Error_404_Endpoint();
