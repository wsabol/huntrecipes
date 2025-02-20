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

    // print_r($recipe);

    $initial_prompt = "Title: $recipe->title\nIngredients:\n";
    $ingredients = $recipe->get_ingredients();
    foreach ($ingredients as $ingredient) {
        $initial_prompt .= $ingredient->value_formatted . "   " . $ingredient->name_formatted . "\n";
    }

    $initial_prompt .= "Instructions:\n" . $recipe->instructions;

    print_r($initial_prompt);

    $result = $client->chat()->create([
        'model' => 'gpt-4o',
        'messages' => [
            ['role' => 'assistant', 'content' => "In 1000 characters or less, create a realistic image generation prompt focused on the visual and aesthetic aspects of the prepared food given this recipe: " . $initial_prompt],
        ],
    ]);

    $image_prompt = $result->choices[0]->message->content; // Hello! How can I assist you today?
    $image_prompt = trim(str_replace("**Prompt for a Realistic Image Generation:**", "", $image_prompt));


    echo "\n\n\n". $image_prompt;

    try {
        $result = $client->images()->create([
            'model' => "dall-e-2",
            'prompt' => $image_prompt,
            'n' => 1,
            'size' => "512x512"
        ]);
    }
    catch (Exception $e) {
        echo "\n\nImage gen failed: " . $e->getMessage();
        echo "\n\n\n";
        sleep(10);
        continue;
    }

    $result = $result->data;
    if (count($result) === 0) {
        echo "No results: \n\n\n";
        sleep(10);
        continue;
    }

    var_dump($result[0]);

    $image_url = $result[0]->url;

    preg_match('/img-[a-zA-Z0-9]+\.png/', $image_url, $matches);
    $base = $matches[0] ?? null;

    if (empty($base)) {
        echo "Could not parse url: $image_url \n\n\n";
        sleep(10);
        continue;
    }

    $image_path = "/assets/images/recipes/generated/$base";
    file_put_contents(RECIPES_ROOT . $image_path, file_get_contents($image_url));

    $recipe->image_filename = $image_path;
    $recipe->save_to_db();

    echo "Success!!! \n\n\n";
    sleep(5);

    // exit;
}
