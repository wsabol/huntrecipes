<?php

use HuntRecipes\Database\SqlController;
use HuntRecipes\Endpoint\Common_Endpoint;
use HuntRecipes\Recipe;

require '../../../includes/common.php';

class Recipe_Endpoint extends Common_Endpoint {

    public function __construct() {
        $this->restrict_access();

        $method = $_SERVER['REQUEST_METHOD'];

        switch ($method) {
            case 'GET':
                $this->get_recipe();
                break;

            case 'POST':
                $this->save_recipe();
                break;

            case 'DELETE':
                $this->delete_recipe();
                break;

            case 'PATCH':
                $this->handle_patch();
                break;

            default:
                $this->method_not_allowed();
        }
    }

    public function save_recipe() {
        $data = array();
        $code = 400;
        $message = '';

        try {

            $request = json_decode(file_get_contents('php://input'));

            if (!isset($request->recipe_id)) {
                throw new Exception("recipe_id is not set");
            }
            if (!isset($request->name)) {
                throw new Exception("name is not set");
            }
            if (!isset($request->company)) {
                throw new Exception("company is not set");
            }
            if (!isset($request->bill_type_id)) {
                throw new Exception("bill_type_id is not set");
            }
            if (!isset($request->day_of_month_due)) {
                throw new Exception("day_of_month_due is not set");
            }
            if (!isset($request->autopay_flag)) {
                throw new Exception("autopay_flag is not set");
            }
            if (!isset($request->login_id)) {
                throw new Exception("login_id is not set");
            }
            if (!isset($request->website)) {
                throw new Exception("website is not set");
            }
            if (!isset($request->username)) {
                throw new Exception("username is not set");
            }
            if (!isset($request->date_opened)) {
                throw new Exception("date_opened is not set");
            }
            if (!isset($request->date_closed)) {
                throw new Exception("date_closed is not set");
            }

            $conn = new SqlController();
            $recipe = new Recipe(0, $conn);
            $recipe->id = $request->recipe_id;
            $recipe->name = $request->name;
            $recipe->company = $request->company;
            $recipe->bill_type_id = (int)$request->bill_type_id;
            $recipe->day_of_month_due = (int)$request->day_of_month_due;
            $recipe->autopay_flag = filter_var($request->autopay_flag, FILTER_VALIDATE_BOOLEAN);
            $recipe->login_id = (int)$request->login_id;
            $recipe->website = $request->website;
            $recipe->username = $request->username;
            $recipe->date_opened = new DateTimeImmutable($request->date_opened);
            $recipe->date_closed = empty($request->date_closed) ? null : new DateTimeImmutable($request->date_closed);

            $success = $recipe->save_to_db();

            if ($success) {
                $code = 200;
                $message = "Success saving object";
            } else {
                $message = $conn->last_message();
            }

        } catch (Exception $e) {
            $message = $e->getMessage();
        }

        echo $this->response($data, $code, $message);
        return true;
    }

    public function get_recipe() {
        $data = array();
        $code = 400;
        $message = '';

        try {

            $request = (object)$_GET;

            if (!isset($request->id)) {
                throw new Exception("id is not set");
            }

            $recipe_id = (int)$request->id;

            $conn = new SqlController();
            $recipe = new Recipe($recipe_id, $conn);

            $data = $recipe->toObject();
            $data->ingredients = $recipe->get_ingredients();
            $data->instructions = $recipe->get_instructions();
            $data->link = $recipe->get_link();
            $data->likes_count = $recipe->get_likes_count();
            $data->liked_by = $recipe->get_users_who_liked_this();

            $code = 200;

        } catch (Exception $e) {
            $message = $e->getMessage();
        }

        echo $this->response($data, $code, $message);
        return true;
    }

    public function delete_recipe() {
        $data = array();
        $code = 400;
        $message = '';

        try {

            $request = json_decode(file_get_contents('php://input'));

            if (!isset($request->recipe_id)) {
                throw new Exception("recipe_id is not set");
            }

            $conn = new SqlController();
            $recipe = new Recipe($request->recipe_id, $conn);
            $success = $recipe->delete_from_db();

            if ($success) {
                $code = 200;
                $message = "Success deleting object";
            } else {
                $message = $conn->last_message();
            }

        } catch (Exception $e) {
            $message = $e->getMessage();
        }

        echo $this->response($data, $code, $message);
        return true;
    }

    private function handle_patch(): bool {
        $request = json_decode(file_get_contents('php://input'));

        if (!isset($request->action)) {
            echo $this->response([], 400, 'action is not set');
            return false;
        }

        switch ($request->action) {
            case 'set-favorite-recipe':
                return $this->set_favorite_recipe($request);

            default:
                echo $this->response(message: "action not handled: {$request->action}");
                return false;
        }
    }

    private function set_favorite_recipe(object $request): bool {
        $data = array();
        $code = 400;
        $message = '';

        try {

            if (!isset($request->user_id)) {
                throw new Exception("user_id is not set");
            }

            if (!isset($request->recipe_id)) {
                throw new Exception("recipe_id is not set");
            }

            if (!isset($request->status)) {
                throw new Exception("status is not set");
            }

            $conn = new SqlController();
            $recipe = new Recipe($request->recipe_id, $conn);
            $user_id = (int)$request->user_id;
            $status = filter_var($request->status, FILTER_VALIDATE_BOOL);

            $recipe->set_user_favorite($user_id, $status);
            $data = $recipe->toObject();
            $data->liked = $status;
            $data->likes_count = $recipe->get_likes_count();

            $message = "Successfully updated user favorite";
            $code = 200;

        } catch (Exception $e) {
            $message = $e->getMessage();
        }

        echo $this->response($data, $code, $message);
        return true;
    }
}

new Recipe_Endpoint();
