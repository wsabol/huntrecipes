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

            case 'PATCH':
                $this->handle_patch();
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

            $data = $user->toObject();
            $is_new_email = ($user->email != $request->email);

            if (!$user->is_safe_to_change_email_to($request->email)) {
                $data->is_new_email = false;
                throw new Exception("This email is already in use by another account");
            }

            $user->name = $request->name;
            $user->email = $request->email;
            $user->save_to_db();

            if ($is_new_email) {
                $user->is_email_verified = false;
                $user->save_to_db();
            }

            $sess = new SessionController();
            $sess->start();
            $sess->set_user($user);
            $sess->close();

            $data = $user->toObject();
            $data->is_new_email = $is_new_email;

            $message = "Successfully updated account";
            $code = 200;

        } catch (Exception $e) {
            $message = $e->getMessage();
        }

        echo $this->response($data, $code, $message);
        return true;
    }

    private function handle_patch(): void {
        $request = json_decode(file_get_contents('php://input'));
        if (empty($request)) {
            parse_str(file_get_contents('php://input'), $request);
        }

        if (!isset($request->action)) {
            echo $this->response([], 400, "action is not set");
            return;
        }

        match ($request->action) {
            'reset-pwd' => $this->reset_password(),
            default => (function($a) {
                echo $this->response([], 400, "action is not recognized: $a");
            })($request->action)
        };
    }

    private function reset_password(): bool {
        $data = array();
        $code = 400;
        $message = '';

        try {

            $request = json_decode(file_get_contents('php://input'));

            if (!isset($request->user_id)) {
                throw new Exception("user_id is not set");
            }
            if (!isset($request->password)) {
                throw new Exception("password is not set");
            }

            $user_id = (int)$request->user_id;
            $encoded_password = (string)$request->password;
            $password = base64_decode($encoded_password);

            if (!$password) {
                throw new Exception("password is invalid");
            }

            $conn = new SqlController();
            $user = new User($user_id, $conn);

            if (!$user->is_enabled()) {
                throw new Exception("Account is not enabled");
            }

            $user->set_password($password);
            $user->save_to_db();

            $user->send_reset_password_confirmation();

            $message = "Successfully sent reset password link";
            $code = 200;

        } catch (Exception $e) {
            $message = $e->getMessage();
        }

        echo $this->response($data, $code, $message);
        return true;
    }
}

new Account_User_Endpoint();
