<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecipeResource extends JsonResource {
    public function toArray(Request $request): array {
        return [
            'id' => $this->id, 'title' => $this->title, 'instructions' => array_values(array_filter(preg_split('/\R/', (string)$this->instructions))),
            'image_filename' => str_starts_with((string)$this->image_filename, '/') ? $this->image_filename : '/' . $this->image_filename,
            'serving_count' => $this->serving_count, 'serving_measure_id' => $this->serving_measure_id,
            'course_id' => $this->course_id, 'cuisine_id' => $this->cuisine_id, 'type_id' => $this->type_id,
            'parent_recipe_id' => $this->parent_recipe_id, 'published_flag' => (bool)$this->published_flag,
            'course' => $this->whenLoaded('course', fn () => $this->course?->name),
            'cuisine' => $this->whenLoaded('cuisine', fn () => $this->cuisine?->name),
            'recipe_type' => $this->whenLoaded('type', fn () => $this->type?->name),
            'chef' => $this->whenLoaded('chef', fn () => $this->chef?->name),
            'chef_id' => $this->chef_id, 'likes_count' => (int)($this->favorited_by_count ?? 0),
            'is_liked' => (bool)($this->is_liked ?? false), 'link' => route('recipes.show', $this->id),
            'ingredients' => $this->whenLoaded('ingredients', fn () => $this->ingredients->map(fn ($item) => [
                'id' => $item->id,
                'raw_ingredient_name' => $item->ingredient?->name ?? '',
                'ingredient_prep' => $item->ingredient_prep,
                'measure_id' => $item->measure_id,
                'amount' => $item->amount,
                'optional_flag' => (bool)$item->optional_flag,
            ])),
        ];
    }
}
