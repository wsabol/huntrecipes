<?php

use HuntRecipes\Database\SqlController;
use HuntRecipes\Endpoint\Common_Endpoint;
use HuntRecipes\User\User;

require '../../../../includes/common.php';

class Account_User_Verify_Endpoint extends Common_Endpoint {

    public function __construct() {
        // $this->restrict_access();

        $method = $_SERVER['REQUEST_METHOD'];

        switch ($method) {
            case 'POST':
                $this->verify_account();
                break;

            default:
                $this->method_not_allowed();
        }
    }

    public function verify_account(): bool {
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
            if ($user->is_email_verified) {
                throw new Exception("Account is already verified");
            }

            $user->send_email_verification();

            $message = "Successfully send verification link";
            $code = 200;

        } catch (Exception $e) {
            $message = $e->getMessage();
        }

        echo $this->response($data, $code, $message);
        return true;
    }
}

new Account_User_Verify_Endpoint();
