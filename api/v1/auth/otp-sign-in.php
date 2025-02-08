<?php

use HuntRecipes\Endpoint\Common_Endpoint;
use HuntRecipes\User\Authenticator;
use HuntRecipes\User\SessionController;
use HuntRecipes\User\User;
use HuntRecipes\User\UserOneTimePassword;

require '../../../includes/common.php';

class Auth_OTP_Login_Endpoint extends Common_Endpoint {

    public function __construct() {
        // $this->restrict_access();

        $method = $_SERVER['REQUEST_METHOD'];

        switch ($method) {
            case 'POST':
                $this->sign_in_with_otp();
                break;

            default:
                $this->method_not_allowed();
        }
    }

    public function sign_in_with_otp(): bool {
        $data = array();
        $code = 400;
        $message = '';

        try {

            $request = json_decode(file_get_contents('php://input'));

            $sess = new SessionController();
            $sess->start();

            if (!isset($request->elogin)) {
                throw new Exception('elogin not set');
            }

            $dLogin = explode(";", base64_decode($request->elogin), 2);
            $email = $dLogin[0];
            $otp_code = @$dLogin[1];

            if (empty($email) || empty($otp_code)) {
                throw new Exception('elogin not valid');
            }

            $auth = new Authenticator();

            $user = User::create_from_email($auth->conn, $email);
            if ($user === false) {
                throw new Exception("Account does not exist: $email");
            }

            $otp = UserOneTimePassword::from_code($user->id, $otp_code, $auth->conn);

            if ($otp === false) {
                throw new Exception("This password is incorrect");
            }
            if (!$otp->is_enabled || $otp->is_expired()) {
                throw new Exception("This password has expired");
            }
            if ($otp->is_used()) {
                throw new Exception("This password has already been used");
            }

            $sess->set_user($user);
            $sess->close();

            if ($request->rememberme) {
                $auth->setPersistentLogin($user->id);
            }

            $message = "Successfully logged in with one-time password";
            $code = 200;

        } catch (Exception $e) {
            $message = $e->getMessage();
        }

        echo $this->response($data, $code, $message);
        return true;
    }
}

new Auth_OTP_Login_Endpoint();
