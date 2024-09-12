<?php

use HuntRecipes\Database\SqlController;
use HuntRecipes\Endpoint\Common_Endpoint;
use HuntRecipes\Recipe;
use HuntRecipes\User\SessionController;

require '../../../includes/common.php';

class Recipe_RecipeOfTheDay_Endpoint extends Common_Endpoint {

    public function __construct() {
        // $this->restrict_access();

        $method = $_SERVER['REQUEST_METHOD'];

        switch ($method) {
            case 'GET':
                $this->get_rotd();
                break;

            default:
                $this->method_not_allowed();
        }
    }

    public function get_rotd() {
        $data = array();
        $code = 400;
        $message = '';

        try {

            $sess = new SessionController();
            $sess->start();

            $conn = new SqlController();
            $recipe = Recipe::recipe_of_the_day($conn);

            $data = $recipe->toObject();
            $data->recipe_type = $recipe->get_report_type();
            $data->course = $recipe->get_course();
            $data->cuisine = $recipe->get_cuisine();
            $data->chef = $recipe->get_chef();
            $data->is_liked = false;
            $data->likes_count = $recipe->get_likes_count();
            $data->link = $recipe->get_link();

            if ($sess->has_user()) {
                $data->is_liked = $recipe->is_liked($sess->user()->id);
            }

            $message = 'Got recipe of the day';
            $code = 200;

        } catch (Exception $e) {
            $message = $e->getMessage();
        }

        echo $this->response($data, $code, $message);
        return true;
    }
}

new Recipe_RecipeOfTheDay_Endpoint();
