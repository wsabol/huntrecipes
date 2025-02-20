<?php

use HuntRecipes\Database\SqlController;
use HuntRecipes\Endpoint\Common_Endpoint;
use HuntRecipes\Recipe;

require '../../../includes/common.php';

class Recipe_List_Endpoint extends Common_Endpoint {

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

            $limit = (int)@$request->limit ?? 0;
            $keyword = (string)@$request->keyword ?? '';
            $recipe_type_id = (int)@$request->recipe_type_id ?? 0;
            $course_id = (int)@$request->course_id ?? 0;
            $cuisine_id = (int)@$request->cuisine_id ?? 0;
            $chef_id = (int)@$request->chef_id ?? 0;
            $ingredients = (array)@$request->ingredients ?? [];

            $conn = new SqlController();
            $data = Recipe::list($conn, [
                'limit' => $limit,
                'keyword' => $keyword,
                'recipe_type_id' => $recipe_type_id,
                'course_id' => $course_id,
                'cuisine_id' => $cuisine_id,
                'chef_id' => $chef_id,
                'ingredients' => $ingredients,
            ]);

            $message = 'Got recipes';
            $code = 200;

        } catch (Exception $e) {
            $message = $e->getMessage();
        }

        echo $this->response($data, $code, $message);
        return true;
    }
}

new Recipe_List_Endpoint();
