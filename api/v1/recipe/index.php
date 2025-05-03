<?php

use HuntRecipes\Database\SqlController;
use HuntRecipes\Endpoint\Common_Endpoint;
use HuntRecipes\Endpoint\FileUploadController;
use HuntRecipes\Ingredient;
use HuntRecipes\Measure\Fraction;
use HuntRecipes\Recipe;
use HuntRecipes\RecipeIngredient;

require '../../../includes/common.php';

class Recipe_Endpoint extends Common_Endpoint {

    public function __construct() {
        $this->restrict_access();

        ini_set('upload_max_filesize', '40M');
        ini_set('post_max_size', '42M');

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

            $request = $this->get_request_data();

            if (!isset($request->chef_id)) {
                throw new Exception("chef_id is not set");
            }
            if (!isset($request->recipe_id)) {
                throw new Exception("recipe_id is not set");
            }
            if (!isset($request->title)) {
                throw new Exception("title is not set");
            }
            if (!isset($request->course_id)) {
                throw new Exception("course_id is not set");
            }
            if (!isset($request->cuisine_id)) {
                throw new Exception("cuisine_id is not set");
            }
            if (!isset($request->type_id)) {
                throw new Exception("type_id is not set");
            }
            if (!isset($request->serving_count)) {
                throw new Exception("serving_count is not set");
            }
            if (!isset($request->serving_measure_id)) {
                throw new Exception("serving_measure_id is not set");
            }
            if (!isset($request->published_flag)) {
                throw new Exception("published_flag is not set");
            }

            if (!isset($request->ingredients)) {
                throw new Exception("ingredients is not set");
            }
            $ingredients = json_decode($request->ingredients);
            if (empty($ingredients)) {
                throw new Exception("The recipe must have ingredients");
            }

            if (!isset($request->instructions)) {
                throw new Exception("instructions is not set");
            }
            $instructions = json_decode($request->instructions);
            if (empty($instructions)) {
                throw new Exception("The recipe must have instructions");
            }

            $conn = new SqlController();
            $recipe = new Recipe((int)$request->recipe_id, $conn);
            $recipe->title = $request->title;
            $recipe->course_id = (int)$request->course_id;
            $recipe->cuisine_id = (int)$request->cuisine_id;
            $recipe->type_id = (int)$request->type_id;
            $recipe->serving_count = (new Fraction($request->serving_count))->decimal;
            $recipe->serving_measure_id = (int)$request->serving_measure_id;
            $recipe->instructions = implode("\n", array_values(array_filter($instructions, 'strlen')));
            $recipe->chef_id = (int)$request->chef_id;
            $recipe->published_flag = filter_var($request->published_flag, FILTER_VALIDATE_BOOL);

            // handle file upload
            if (isset($_FILES['recipe_image'])) {
                $uploader = new FileUploadController('recipe_image');

                // Check if it's an image or audio file
                if (!$uploader->is_valid()) {
                    throw new Exception("Error handling recipe image: " . $uploader->get_error());
                }

                // Check if it's an image or audio file
                if (!$uploader->is_image()) {
                    throw new Exception("Error handling recipe image: Only image files are allowed");
                }

                // Move file to permanent location
                $new_file = $uploader->move(Recipe::IMAGES_DIR);
                $recipe->image_filename = "/" . $new_file;
            }
            elseif (isset($request->image_filename)) {
                $recipe->image_filename = $request->image_filename;
            }

            $success = $recipe->save_to_db();

            if (!$success) {
                throw new Exception("Failed to save recipe.");
            }

            $recipe_ingredients = [];
            foreach ($ingredients as $ingredient) {
                $i = Ingredient::create_from_name($ingredient->raw_ingredient_name, $conn);
                $recipe_ingredients[] = RecipeIngredient::create(
                    $conn,
                    $recipe,
                    $i,
                    $ingredient->ingredient_prep,
                    $ingredient->measure_id,
                    (new Fraction($ingredient->amount))->decimal,
                    filter_var($ingredient->optional, FILTER_VALIDATE_BOOL),
                );
            }

            $success = $recipe->set_recipe_ingredients($recipe_ingredients);

            if ($success) {
                $code = 200;
                $message = "Success saving object";
            } else {
                $message = "Error saving object";
            }

            $data = $recipe->toObject();
            $data->ingredients = $recipe->get_ingredients();
            $data->instructions = $recipe->get_instructions();
            $data->link = $recipe->get_link();
            $data->likes_count = $recipe->get_likes_count();
            $data->liked_by = $recipe->get_users_who_liked_this();

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
            if (empty($request)) {
                parse_str(file_get_contents('php://input'), $request);
            }
            if (empty($request)) {
                $request = (object)$_REQUEST;
            }

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

            case 'set-image-filename':
                return $this->set_image_filename($request);

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

    private function set_image_filename(object $request): bool {
        $data = array();
        $code = 400;
        $message = '';

        try {

            if (!isset($request->recipe_id)) {
                throw new Exception("recipe_id is not set");
            }
            if (!isset($request->value)) {
                throw new Exception("value is not set");
            }
            if (!file_exists(RECIPES_ROOT . $request->value)) {
                throw new Exception("file does not exist");
            }

            $conn = new SqlController();
            $recipe = new Recipe($request->recipe_id, $conn);
            $recipe->image_filename = $request->value;
            $recipe->save_to_db();

            $data = $recipe->toObject();
            if (!$_ENV['PRODUCTION']) {
                $data->image_filename = 'https://huntrecipes.willsabol.com' . $recipe->image_filename;
            }

            $message = "Successfully updated image filename";
            $code = 200;

        } catch (Exception $e) {
            $message = $e->getMessage();
        }

        echo $this->response($data, $code, $message);
        return true;
    }
}

new Recipe_Endpoint();
