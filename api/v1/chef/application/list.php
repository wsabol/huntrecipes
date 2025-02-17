<?php

use HuntRecipes\Database\SqlController;
use HuntRecipes\Endpoint\Common_Endpoint;
use HuntRecipes\User\ChefApplication;

require '../../../../includes/common.php';

class Chef_Application_List_Endpoint extends Common_Endpoint {

    public function __construct() {
        // $this->restrict_access();

        $method = $_SERVER['REQUEST_METHOD'];

        switch ($method) {
            case 'GET':
                $this->list();
                break;

            default:
                $this->method_not_allowed();
        }
    }

    public function list() {
        $data = array();
        $code = 400;
        $message = '';

        try {

            $request = (object)$_GET;

            $chef_application_status_id = 0;
            if (!empty(@$request->chef_application_status_id)) {
                $chef_application_status_id = (int)$request->chef_application_status_id;
            }

            $user_id = 0;
            if (!empty(@$request->user_id)) {
                $user_id = (int)$request->user_id;
            }

            $date_from = '';
            if (!empty(@$request->date_from)) {
                $date_from = (new DateTime($request->date_from))->format('Y-m-d');
            }

            $date_to = '';
            if (!empty(@$request->date_to)) {
                $date_to = (new DateTime($request->date_to))->format('Y-m-d');
            }

            $conn = new SqlController();
            $data = ChefApplication::list($conn, [
                'chef_application_status_id' => $chef_application_status_id,
                'user_id' => $user_id,
                'date_from' => $date_from,
                'date_to' => $date_to,
            ]);

            $message = "Got applications";
            $code = 200;

        } catch (Exception $e) {
            $message = $e->getMessage();
        }

        echo $this->response($data, $code, $message);
        return true;
    }
}

new Chef_Application_List_Endpoint();
