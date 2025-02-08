<?php

use HuntRecipes\Database\SqlController;
use HuntRecipes\Endpoint\Common_Endpoint;
use HuntRecipes\User\User;

require '../../../../includes/common.php';

class Account_User_Identify_Endpoint extends Common_Endpoint {

    public function __construct() {
        // $this->restrict_access();

        $method = $_SERVER['REQUEST_METHOD'];

        switch ($method) {
            case 'GET':
                $this->identify_account();
                break;

            default:
                $this->method_not_allowed();
        }
    }

    public function identify_account(): bool {
        $data = array();
        $code = 400;
        $message = '';

        try {

            $request = (object)$_GET;

            if (!isset($request->email)) {
                throw new Exception("email is not set");
            }

            $conn = new SqlController();
            $user = User::create_from_email($conn, $request->email);

            if (!$user) {
                throw new Exception("Account is not found");
            }

            if (!$user->is_enabled()) {
                throw new Exception("Account is not enabled");
            }

            $data = $user->toObject();
            $message = "Found valid account for email";
            $code = 200;

        } catch (Exception $e) {
            $message = $e->getMessage();
        }

        echo $this->response($data, $code, $message);
        return true;
    }
}

new Account_User_Identify_Endpoint();
