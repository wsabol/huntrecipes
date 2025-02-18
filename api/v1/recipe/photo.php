<?php

use HuntRecipes\Endpoint\FileUploadController;
use HuntRecipes\Database\SqlController;
use HuntRecipes\Endpoint\Common_Endpoint;
use HuntRecipes\User\SessionController;
use HuntRecipes\Recipe;

require '../../../includes/common.php';

class Account_Recipe_Photo_Endpoint extends Common_Endpoint {

    public function __construct() {
        $this->restrict_access();

        $method = $_SERVER['REQUEST_METHOD'];

        switch ($method) {
            case 'POST':
                $this->set_recipe_picture();
                break;

            case 'DELETE':
                $this->remove_recipe_picture();
                break;

            default:
                $this->method_not_allowed();
        }
    }

    private function set_recipe_picture(): bool {
        $data = array();
        $code = 400;
        $message = '';

        try {

            $request = (object)$_POST;

            if (!isset($request->recipe_id)) {
                throw new Exception("recipe_id is not set");
            }

            if (!isset($_FILES['recipe_photo'])) {
                throw new Exception("recipe_photo is not set");
            }

            $recipe_id = (int)$request->recipe_id;
            $conn = new SqlController();
            $recipe = new Recipe($recipe_id, $conn);

            $uploader = new FileUploadController('recipe_photo');

            // Check if it's an image or audio file
            if (!$uploader->is_valid()) {
                throw new Exception("Error handling recipe image: " . $uploader->get_error());
            }
            // Check if it's an image or audio file
            if (!$uploader->is_image()) {
                throw new Exception("Error handling recipe image: Only image files are allowed");
            }

            // Move file to permanent location
            $recipe_image = $uploader->move(Recipe::IMAGES_DIR);

            $recipe->image_filename = "/" . $recipe_image;
            $recipe->save_to_db();

            $data = $recipe->toObject();
            $message = "Successfully set new recipe image";
            $code = 200;

        } catch (Exception $e) {
            $message = $e->getMessage();
        }

        echo $this->response($data, $code, $message);
        return true;
    }

    private function remove_recipe_picture(): bool {
        $data = array();
        $code = 400;
        $message = '';

        try {

            $request = json_decode(file_get_contents('php://input'));
            if (empty($request)) {
                parse_str(file_get_contents('php://input'), $request);
            }
            if (empty($request)) {
                $request = (object)$_REQUEST;
            }

            if (!isset($request->recipe_id)) {
                throw new Exception("recipe_id is not set");
            }

            $recipe_id = (int)$request->recipe_id;
            $conn = new SqlController();
            $recipe = new Recipe($recipe_id, $conn);

            $recipe->image_filename = '/assets/images/recipes/generic_recipe.jpg';
            $recipe->save_to_db();

            $data = $recipe->toObject();
            $message = "Successfully reset recipe image";
            $code = 200;

        } catch (Exception $e) {
            $message = $e->getMessage();
        }

        echo $this->response($data, $code, $message);
        return true;
    }
}

new Account_Recipe_Photo_Endpoint();
