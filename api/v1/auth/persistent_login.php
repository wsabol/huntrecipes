<?php

use HuntRecipes\Endpoint\Common_Endpoint;
use HuntRecipes\User\Authenticator;
use HuntRecipes\User\SessionController;

require '../../../includes/common.php';

class Auth_PersistentLogin_Endpoint extends Common_Endpoint {

    public function __construct() {
        // $this->restrict_access();

        $method = $_SERVER['REQUEST_METHOD'];

        switch ($method) {
            case 'POST':
                $this->persistent_login();
                break;

            default:
                $this->method_not_allowed();
        }
    }

    public function persistent_login() {
        $data = array();
        $code = 400;
        $message = '';

        try {

            $request = json_decode(file_get_contents('php://input'));

            $sess = new SessionController();
            $auth = new Authenticator();

            if (!isset($request->uname_auth)) {
                throw new Exception('uname_auth not set');
            }

            $uname_auth = $request->uname_auth;

            if (!Authenticator::validateLoginCookie($uname_auth)) {
                throw new Exception("uname_auth is not valid");
            }

            $user_id = $auth->checkCookieLogin($_COOKIE['uname_auth']);
            if (empty($user_id)) {
                throw new Exception("unable to authenticate");
            }

            $sess->set_user(new User($user_id));
            $sess->close();

            $message = "Successfully logged in";
            $code = 200;

        } catch (Exception $e) {
            $message = $e->getMessage();
        }

        echo $this->response($data, $code, $message);
        return true;
    }
}

new Auth_PersistentLogin_Endpoint();
