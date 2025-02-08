<?php

use HuntRecipes\Database\SqlController;
use HuntRecipes\Endpoint\Common_Endpoint;
use HuntRecipes\User\User;
use HuntRecipes\User\UserOneTimePassword;

require '../../../../includes/common.php';

class Account_User_SignInOTP_Endpoint extends Common_Endpoint {

    public function __construct() {
        // $this->restrict_access();

        $method = $_SERVER['REQUEST_METHOD'];

        switch ($method) {
            case 'POST':
                $this->create_opt_and_send();
                break;

            default:
                $this->method_not_allowed();
        }
    }

    public function create_opt_and_send(): bool {
        $data = array();
        $code = 400;
        $message = '';

        try {

            $request = json_decode(file_get_contents('php://input'));

            if (!isset($request->user_id)) {
                throw new Exception("user_id is not set");
            }

            if (!isset($request->email)) {
                throw new Exception("email is not set");
            }

            $conn = new SqlController();
            $user = new User($request->user_id, $conn);

            if (empty($user->email)) {
                throw new Exception("User does not exist: $request->user_id");
            }
            if (!$user->is_enabled()) {
                throw new Exception("This account is not enabled (id:$request->user_id)");
            }

            $otp = UserOneTimePassword::create_new_for_user($user, $conn);
            $otp->send_code_to_user($user->email);

            $data['user'] = $user->toObject();
            $data['otp'] = $otp->toObject();

            $message = "Successfully sent OPT to user";
            $code = 200;

        } catch (Exception $e) {
            $message = $e->getMessage();
        }

        echo $this->response($data, $code, $message);
        return true;
    }
}

new Account_User_SignInOTP_Endpoint();
