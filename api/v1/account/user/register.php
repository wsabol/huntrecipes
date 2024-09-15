<?php

use HuntRecipes\Database\SqlController;
use HuntRecipes\Endpoint\Common_Endpoint;
use HuntRecipes\User\User;

require '../../../../includes/common.php';

class Account_User_Register_Endpoint extends Common_Endpoint {

    public function __construct() {
        // $this->restrict_access();

        $method = $_SERVER['REQUEST_METHOD'];

        switch ($method) {
            case 'POST':
                $this->create_account();
                break;

            default:
                $this->method_not_allowed();
        }
    }

    public function create_account(): bool {
        $data = array();
        $code = 400;
        $message = '';

        try {

            $request = json_decode(file_get_contents('php://input'));

            if (!isset($request->name)) {
                throw new Exception("name is not set");
            }

            if (!isset($request->email)) {
                throw new Exception("email is not set");
            }

            if (!isset($request->password)) {
                throw new Exception("password is not set");
            }

            $conn = new SqlController();
            $user = new User(0, $conn);
            $user->name = $request->name;
            $user->set_password($request->password);

            // defaults
            $user->account_status_id = 1;
            $user->profile_picture = "/assets/images/users/generic_avatar.jpg";
            $user->chef_app_pending = 0;
            $user->is_chef = false;
            $user->is_email_verified = false;
            $user->date_created = new DateTimeImmutable();

            if (!$user->is_safe_to_change_email_to($request->email)) {
                throw new Exception("This email is already in use by another account");
            }

            $user->email = $request->email;
            $user->is_email_verified = false;
            $user->save_to_db();

            // send_email_verification
            $user->send_email_verification();

            $message = "Successfully create account";
            $code = 200;

        } catch (Exception $e) {
            $message = $e->getMessage();
        }

        echo $this->response($data, $code, $message);
        return true;
    }
}

new Account_User_Register_Endpoint();
