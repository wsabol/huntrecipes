<?php

use HuntRecipes\Database\SqlController;
use HuntRecipes\Endpoint\Common_Endpoint;
use HuntRecipes\Chef;

require '../../../../includes/common.php';

class Account_Chef_Endpoint extends Common_Endpoint {

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

            if (!isset($request->chef_id)) {
                throw new Exception("chef_id is not set");
            }
            if (!isset($request->name)) {
                throw new Exception("name is not set");
            }
            if (!isset($request->is_male)) {
                throw new Exception("is_male is not set");
            }
            if (!isset($request->favorite_foods)) {
                throw new Exception("favorite_foods is not set");
            }
            if (!isset($request->story)) {
                throw new Exception("story is not set");
            }
            if (!isset($request->wisdom)) {
                throw new Exception("wisdom is not set");
            }

            $conn = new SqlController();
            $chef = new Chef(0, $conn);
            $chef->id = $request->chef_id;
            $chef->name = $request->name;
            $chef->is_male = $request->is_male;
            $chef->favorite_foods = $request->favorite_foods;
            $chef->story = $request->story;
            $chef->wisdom = $request->wisdom;

            $chef->save_to_db();

            $message = "Successfully updated account";
            $code = 200;

        } catch (Exception $e) {
            $message = $e->getMessage();
        }

        echo $this->response($data, $code, $message);
        return true;
    }
}

new Account_Chef_Endpoint();
