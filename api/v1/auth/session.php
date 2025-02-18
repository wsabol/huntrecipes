<?php

use HuntRecipes\Endpoint\Common_Endpoint;
use HuntRecipes\User\SessionController;

require '../../../includes/common.php';

class Auth_Session_Endpoint extends Common_Endpoint {

    public function __construct() {
        $this->restrict_access();

        $method = $_SERVER['REQUEST_METHOD'];

        switch ($method) {
            case 'POST':
                $this->post_session();
                break;

            default:
                $this->method_not_allowed();
        }
    }

    public function post_session() {
        $data = array();
        $code = 400;
        $message = '';

        $sess = new SessionController();
        $sess->start();

        try {
            $request = json_decode(file_get_contents('php://input'));

            if (!isset($request->action)) {
                throw new Exception('action is not set');
            }

            $action = $request->action;

            switch ($action) {
                case 'set-fyear':

                    if (!isset($request->value)) {
                        throw new Exception('value is not set');
                    }

                    $sess->set_financial_year($request->value);
                    break;

                default:
                    throw new Exception('action not recognized');
            }

            $code = 200;

        } catch (Exception $e) {
            $message = $e->getMessage();
        }

        $sess->close();

        echo $this->response($data, $code, $message);
        return true;
    }
}

new Auth_Session_Endpoint();
