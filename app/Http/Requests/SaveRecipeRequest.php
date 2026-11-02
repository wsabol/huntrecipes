<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveRecipeRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    protected function prepareForValidation(): void {
        $instructions = $this->input('instructions');
        if (is_string($instructions) && str_starts_with(trim($instructions), '[')) {
            $decoded = json_decode($instructions, true);
            if (is_array($decoded)) {
                $instructions = implode("\n", array_filter(array_map('trim', $decoded)));
            }
        }
        $ingredients = $this->input('ingredients');
        if (is_string($ingredients)) {
            $ingredients = json_decode($ingredients, true);
        }
        $image = $this->input('image_filename');
        if ($image === '#' || $image === '') {
            $image = '/assets/images/recipes/generic_recipe.jpg';
        }
        $this->merge(['instructions' => $instructions, 'ingredients' => $ingredients ?: [], 'published_flag' => $this->boolean('published_flag'), 'image_filename' => $image]);
    }

    public function rules(): array {
        return [
            'title' => ['required', 'string', 'max:255'], 'instructions' => ['required', 'string'],
            'course_id' => ['nullable', 'integer'], 'cuisine_id' => ['nullable', 'integer'], 'type_id' => ['nullable', 'integer'],
            'serving_count' => ['nullable', 'numeric', 'min:0'], 'serving_measure_id' => ['nullable', 'integer'],
            'parent_recipe_id' => ['nullable', 'integer'], 'published_flag' => ['sometimes', 'boolean'],
            'ingredients' => ['array'], 'ingredients.*.raw_ingredient_name' => ['required', 'string', 'max:255'],
            'ingredients.*.ingredient_prep' => ['nullable', 'string', 'max:255'], 'ingredients.*.measure_id' => ['required', 'integer'],
            'ingredients.*.amount' => ['nullable'], 'ingredients.*.optional' => ['sometimes', 'boolean'],
            'recipe_image' => ['nullable', 'image', 'max:8192'], 'image_filename' => ['nullable', 'string', 'max:500', 'regex:#^/(?:storage/recipes|assets/images/recipes)/#', Rule::in(['/assets/images/recipes/generic_recipe.jpg', $this->route('recipe')?->image_filename])],
        ];
    }
}
