<?php

namespace App\Services;

use App\Models\Recipe;
use Illuminate\Support\Facades\Storage;
use OpenAI;
use RuntimeException;

final class RecipeImageService {
    public function generate(Recipe $recipe, ?string $requestedPrompt = null): array {
        $apiKey = (string)config('services.openai.api_key');
        if ($apiKey === '') {
            throw new RuntimeException('OpenAI image generation is not configured.');
        }

        $recipe->loadMissing('ingredients.ingredient');
        $prompt = trim((string)$requestedPrompt) ?: sprintf(
            'A realistic, appetizing editorial food photograph of %s. Ingredients: %s. Natural light, clean plating, no text or people.',
            $recipe->title,
            $recipe->ingredients->pluck('ingredient.name')->filter()->join(', '),
        );

        $response = OpenAI::client($apiKey)->images()->create([
            'model' => 'gpt-image-2',
            'prompt' => mb_substr($prompt, 0, 4000),
            'n' => 1,
            'size' => '1024x1024',
        ]);
        $image = base64_decode($response->data[0]->b64_json ?? '', true);
        if ($image === false || $image === '') {
            throw new RuntimeException('The image provider returned no image data.');
        }

        $path = 'recipes/generated/' . bin2hex(random_bytes(16)) . '.png';
        Storage::disk('public')->put($path, $image);

        return ['image_prompt' => $prompt, 'generated_image' => '/storage/' . $path];
    }
}
