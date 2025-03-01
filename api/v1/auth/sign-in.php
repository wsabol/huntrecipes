<?php

use HuntRecipes\Endpoint\Common_Endpoint;
use HuntRecipes\User\Authenticator;
use HuntRecipes\User\SessionController;
use HuntRecipes\User\User;

require '../../../includes/common.php';

class Auth_Login_Endpoint extends Common_Endpoint {

    public function __construct() {
        // $this->restrict_access();

        $method = $_SERVER['REQUEST_METHOD'];

        switch ($method) {
            case 'POST':
                $this->sign_in();
                break;

            default:
                $this->method_not_allowed();
        }
    }

    public function sign_in(): bool {
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

            $decoded = base64_decode($request->elogin);
            if (!$decoded) {
                throw new Exception('elogin not valid');
            }

            $dLogin = explode(";", $decoded, 2);
            $email = $dLogin[0];
            $password = @$dLogin[1];

            if (empty($email) || empty($password)) {
                throw new Exception('elogin not valid');
            }

            $auth = new Authenticator();

            $user = User::create_from_email($auth->conn, $email);

            if ($user === false) {
                throw new Exception("Email does not match any account. Try a different address");
            }

            $is_correct_password = password_verify($password, $user->get_password());

            // legacy
            if (!$is_correct_password) {
                $is_correct_password = sha1($password) === $user->get_password();
            }

            if (!$is_correct_password) {
                throw new Exception("Email/Password provided do not match what we have on record");
            }

            if (!$user->is_enabled()) {
                throw new Exception("Please contact your system administrator.");
            }

            $sess->set_user($user);
            $sess->close();

            if ($request->rememberme) {
                $auth->setPersistentLogin($user->id);
            }

            $message = "Successfully logged in";
            $code = 200;

        } catch (Exception $e) {
            $message = $e->getMessage();
        }

        echo $this->response($data, $code, $message);
        return true;
    }
}

new Auth_Login_Endpoint();
