<?php

namespace App\Services;

use App\Models\Recipe;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class RecipeQueryService {
    public function search(array $filters, ?User $user = null): LengthAwarePaginator {
        $query = Recipe::query()
            ->with(['chef', 'course', 'cuisine', 'type'])
            ->withCount('favoritedBy')
            ->where('published_flag', true);

        if ($user) {
            $query->withExists(['favoritedBy as is_liked' => fn($q) => $q->where('User.id', $user->id)]);
        }
        if ($keyword = trim((string)($filters['keyword'] ?? ''))) {
            $query->where(fn($q) => $q->where('title', 'like', "%{$keyword}%")->orWhere('instructions', 'like', "%{$keyword}%"));
        }
        foreach (['course_id', 'cuisine_id', 'chef_id'] as $column) {
            if (!empty($filters[$column])) {
                $query->where($column, (int)$filters[$column]);
            }
        }
        if (!empty($filters['recipe_type_id'])) {
            $query->where('type_id', (int)$filters['recipe_type_id']);
        }
        if (!empty($filters['ingredients'])) {
            $ingredients = array_filter((array)$filters['ingredients']);
            $query->whereHas('ingredients.ingredient', fn($q) => $q->whereIn('name', $ingredients));
        }

        $hasFilters = array_filter($filters, fn($value) => $value !== null && $value !== '' && $value !== [] && $value !== 0 && $value !== '0');
        $hasFilters ? $query->orderBy('title') : $query->inRandomOrder();

        return $query->paginate(min(max((int)($filters['per_page'] ?? 24), 1), 100));
    }
}
