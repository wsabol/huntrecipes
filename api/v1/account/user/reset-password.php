<?php

use HuntRecipes\Database\SqlController;
use HuntRecipes\Endpoint\Common_Endpoint;
use HuntRecipes\User\User;

require '../../../../includes/common.php';

class Account_User_ResetPassword_Endpoint extends Common_Endpoint {

    public function __construct() {
        $this->restrict_access();

        $method = $_SERVER['REQUEST_METHOD'];

        switch ($method) {
            case 'POST':
                $this->send_reset_link();
                break;

            default:
                $this->method_not_allowed();
        }
    }

    public function send_reset_link(): bool {
        $data = array();
        $code = 400;
        $message = '';

        try {

            $request = json_decode(file_get_contents('php://input'));

            if (!isset($request->user_id)) {
                throw new Exception("user_id is not set");
            }

            $conn = new SqlController();
            $user = new User($request->user_id, $conn);

            if (!$user->is_enabled()) {
                throw new Exception("Account is not enabled");
            }

            $user->send_reset_password();

            $message = "Successfully sent reset password link";
            $code = 200;

        } catch (Exception $e) {
            $message = $e->getMessage();
        }

        echo $this->response($data, $code, $message);
        return true;
    }
}

new Account_User_ResetPassword_Endpoint();
