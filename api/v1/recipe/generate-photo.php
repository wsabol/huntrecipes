<?php

use HuntRecipes\Database\SqlController;
use HuntRecipes\Endpoint\Common_Endpoint;
use HuntRecipes\Recipe;

require '../../../includes/common.php';

class Account_Recipe_Generate_Photo_Endpoint extends Common_Endpoint {

    public function __construct() {
        $this->restrict_access();

        $method = $_SERVER['REQUEST_METHOD'];

        switch ($method) {
            case 'POST':
                $this->generate_photo();
                break;

            default:
                $this->method_not_allowed();
        }
    }

    private function generate_photo(): void {
        $data = [
            'image_prompt' => '',
            'generated_image' => '',
        ];

        $code = 400;
        $message = '';

        try {

            $request = $this->get_request_data();
            $request = json_decode(file_get_contents('php://input'));

            if (!isset($request->recipe_id)) {
                throw new Exception("recipe_id is not set");
            }

            $recipe_id = (int)$request->recipe_id;
            $conn = new SqlController();
            $recipe = new Recipe($recipe_id, $conn);

            $image_prompt = "";
            if (isset($request->image_prompt)) {
                $image_prompt = $request->image_prompt;
            }
            if (empty($image_prompt)) {
                $image_prompt = $recipe->get_ai_recipe_image_prompt();
            }
            if (empty($image_prompt)) {
                throw new Exception("Unable to generate image prompt");
            }

            $data['image_prompt'] = $image_prompt;

            $generated_image = $recipe->generate_ai_recipe_image($image_prompt);
            if (empty($generated_image)) {
                throw new Exception("Unable to generate image");
            }

            $data['generated_image'] = $generated_image;

            if (!file_exists(RECIPES_ROOT . $generated_image)) {
                throw new Exception("Generated image does not exist");
            }

            $message = "Successfully generated recipe image";
            $code = 200;

        } catch (Exception $e) {
            $message = $e->getMessage();
        }

        echo $this->response($data, $code, $message);
    }
}

new Account_Recipe_Generate_Photo_Endpoint();
