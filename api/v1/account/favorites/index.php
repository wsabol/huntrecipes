<?php

use HuntRecipes\Database\SqlController;
use HuntRecipes\Endpoint\Common_Endpoint;
use HuntRecipes\User\User;

require '../../../../includes/common.php';

class Account_Favorites_Endpoint extends Common_Endpoint {

    public function __construct() {
        $this->restrict_access();

        $method = $_SERVER['REQUEST_METHOD'];

        switch ($method) {
            case 'GET':
                $this->get_favorites();
                break;

            default:
                $this->method_not_allowed();
        }
    }

    public function get_favorites(): bool {
        $data = array();
        $code = 400;
        $message = '';

        try {

            $request = (object)$_GET;

            if (!isset($request->user_id)) {
                throw new Exception("user_id is not set");
            }

            $conn = new SqlController();
            $user = new User($request->user_id, $conn);
            $data = $user->get_favorites();

            $message = "Got user favorites";
            $code = 200;

        } catch (Exception $e) {
            $message = $e->getMessage();
        }

        echo $this->response($data, $code, $message);
        return true;
    }
}

new Account_Favorites_Endpoint();
