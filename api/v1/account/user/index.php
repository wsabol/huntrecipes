<?php

use HuntRecipes\Database\SqlController;
use HuntRecipes\Endpoint\Common_Endpoint;
use HuntRecipes\User\SessionController;
use HuntRecipes\User\User;

require '../../../../includes/common.php';

class Account_User_Endpoint extends Common_Endpoint {

    public function __construct() {
        $this->restrict_access();

        $method = $_SERVER['REQUEST_METHOD'];

        switch ($method) {
            case 'PUT':
                $this->update_account();
                break;

            default:
                $this->method_not_allowed();
        }
    }

    public function update_account(): bool {
        $data = array();
        $code = 400;
        $message = '';

        try {

            $request = json_decode(file_get_contents('php://input'));

            if (!isset($request->user_id)) {
                throw new Exception("user_id is not set");
            }

            if (!isset($request->name)) {
                throw new Exception("name is not set");
            }

            if (!isset($request->email)) {
                throw new Exception("email is not set");
            }

            $conn = new SqlController();
            $user = new User($request->user_id, $conn);
            $user->name = $request->name;
            $user->save_to_db();

            $is_new_email = ($user->email != $request->email);

            if (!$user->is_safe_to_change_email_to($request->email)) {
                throw new Exception("This email is already in use by another account");
            }

            $user->email = $request->email;
            $user->save_to_db();

            if ($is_new_email) {
                $user->is_email_verified = false;
                $user->save_to_db();

                // todo send_email_verification
                // $user->send_email_verification();
            }

            $sess = new SessionController();
            $sess->start();
            $sess->set_user($user);
            $sess->close();

            $message = "Successfully updated account";
            $code = 200;

        } catch (Exception $e) {
            $message = $e->getMessage();
        }

        echo $this->response($data, $code, $message);
        return true;
    }
}

new Account_User_Endpoint();
