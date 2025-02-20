<?php

use HuntRecipes\Database\SqlController;
use HuntRecipes\Recipe;
use Unirest\Request;

include __DIR__ . '/includes/common.php';

$yourApiKey = $_ENV['OPENAI_API_KEY'];
$client = OpenAI::client($yourApiKey);

$conn = new SqlController();
$recipes = Recipe::list($conn, []);

foreach ($recipes as $r) {
    $recipe = new Recipe($r->id, $conn);

    if (str_contains($recipe->instructions, "Â")) {
        print_r($recipe->instructions);
        $recipe->instructions = str_replace("Â", "", $recipe->instructions);
        print_r($recipe->instructions);
        $recipe->save_to_db();
    }

    if (!str_contains($recipe->image_filename, 'generic_recipe')) {
        continue;
    }

    try {

        // print_r($recipe);
        $image_prompt = $recipe->get_ai_recipe_image_prompt();
        if (empty($image_prompt)) {
            echo "No prompt: \n\n\n";
            sleep(10);
            continue;
        }

        echo "\n\n\n". $image_prompt;

        $generated_image = $recipe->generate_ai_recipe_image($image_prompt);
        if (empty($generated_image)) {
            echo "No image: \n\n\n";
            sleep(10);
            continue;
        }


        $recipe->image_filename = $generated_image;
        $recipe->save_to_db();

        echo "Success!!! \n\n\n";
        sleep(5);

    }
    catch (Exception $e) {
        echo "\n\nImage gen failed: " . $e->getMessage();
        echo "\n\n\n";
        sleep(10);
        continue;
    }

}
