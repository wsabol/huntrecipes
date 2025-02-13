<?php

use HuntRecipes\Database\SqlController;
use HuntRecipes\Endpoint\Common_Endpoint;
use HuntRecipes\Chef;
use HuntRecipes\User\SessionController;
use HuntRecipes\User\User;

require '../../../includes/common.php';

class Chef_Recipes_Endpoint extends Common_Endpoint {

    public function __construct() {
        // $this->restrict_access();

        $method = $_SERVER['REQUEST_METHOD'];

        switch ($method) {
            case 'GET':
                $this->get_recipes();
                break;

            default:
                $this->method_not_allowed();
        }
    }

    public function get_recipes() {
        $data = array();
        $code = 400;
        $message = '';

        try {

            $request = (object)$_GET;

            $conn = new SqlController();
            $chef = false;

            if (isset($request->chef_id)) {
                $chef = new Chef($request->chef_id, $conn);
            }
            elseif (isset($request->user_id)) {
                $user = new User($request->user_id, $conn);
                if (!$user->is_chef || $user->chef_id === 0) {
                    throw new Exception('User is not a chef');
                }
                $chef = new Chef($user->chef_id, $conn);
            }

            if (!$chef) {
                throw new Exception('chef not found. Expected valid chef_id or user_id');
            }

            $user_id = 0;
            $sess = new SessionController();
            if ($sess->has_user()) {
                $user_id = $sess->user()->id;
            }

            $include_drafts = filter_var($request->include_drafts, FILTER_VALIDATE_BOOLEAN);

            $data = $chef->get_recipes($user_id, $include_drafts);

            $code = 200;

        } catch (Exception $e) {
            $message = $e->getMessage();
        }

        echo $this->response($data, $code, $message);
        return true;
    }
}

new Chef_Recipes_Endpoint();
