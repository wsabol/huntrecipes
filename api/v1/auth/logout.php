<?php

use HuntRecipes\Endpoint\Common_Endpoint;
use HuntRecipes\User\SessionController;

require '../../../includes/common.php';

class Auth_Logout_Endpoint extends Common_Endpoint {

    public function __construct() {
        $this->restrict_access();

        $method = $_SERVER['REQUEST_METHOD'];

        switch ($method) {
            case 'POST':
            case 'GET':
                $this->logout();
                break;

            default:
                $this->method_not_allowed();
        }
    }

    public function logout() {
        $data = array();
        $code = 400;
        $message = '';

        try {

            $sess = new SessionController();
            $sess->logout();

            $message = "Successfully logged out";
            $code = 200;

        } catch (Exception $e) {
            $message = $e->getMessage();
        }

        echo $this->response($data, $code, $message);
        return true;
    }
}

new Auth_Logout_Endpoint();
