<?php

namespace App\Http\Controllers;

use App\Models\Chef;
use App\Models\ChefApplication;
use App\Models\Recipe;
use App\Support\Twig\TwigRenderer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

final class PageController extends Controller {
    public function __construct(private readonly TwigRenderer $twig) {
    }

    public function root(Request $request) {
        return redirect()->route($request->user() ? 'home' : 'welcome');
    }

    public function welcome() {
        return $this->page('pages/welcome.twig', 'Welcome');
    }

    public function about() {
        return $this->page('pages/about.twig', 'About');
    }

    public function contact() {
        return $this->page('pages/contact.twig', 'Contact');
    }

    public function privacy() {
        return $this->page('pages/privacy.twig', 'Privacy Policy');
    }

    public function signIn(Request $request) {
        $returnUrl = $request->query('return');
        if (!is_string($returnUrl) || !str_starts_with($returnUrl, '/') || str_starts_with($returnUrl, '//')) {
            $returnUrl = route('home');
        }

        return $request->user() ? redirect()->route('home') : $this->page('pages/sign-in.twig', 'Sign In', ['return_url' => $returnUrl]);
    }

    public function finishEmailSignIn() {
        return $this->page('pages/email-sign-in-finish.twig', 'Finish Sign In');
    }

    public function home() {
        $categories = collect();
        try {
            $recipeTypes = DB::table('RecipeType as category')
                ->selectRaw("category.id as entity_id, 'recipe_type' as ctype, category.name, category.icon")
                ->selectSub(
                    DB::table('Recipe')->selectRaw('count(*)')
                        ->whereColumn('Recipe.type_id', 'category.id')
                        ->where('Recipe.published_flag', true),
                    'recipe_count',
                );

            $cuisines = DB::table('Cuisine as category')
                ->selectRaw("category.id as entity_id, 'cuisine' as ctype, category.name, category.icon")
                ->selectSub(
                    DB::table('Recipe')->selectRaw('count(*)')
                        ->whereColumn('Recipe.cuisine_id', 'category.id')
                        ->where('Recipe.published_flag', true),
                    'recipe_count',
                );

            $courses = DB::table('Course as category')
                ->selectRaw("category.id as entity_id, 'course' as ctype, category.name, category.icon")
                ->selectSub(
                    DB::table('Recipe')->selectRaw('count(*)')
                        ->whereColumn('Recipe.course_id', 'category.id')
                        ->where('Recipe.published_flag', true),
                    'recipe_count',
                );

            $classCycle = ['light', 'medium', 'dark', 'medium'];
            $categories = DB::query()
                ->fromSub($recipeTypes->union($cuisines)->union($courses), 'category')
                ->where('icon', '!=', '')
                ->orderByDesc('recipe_count')
                ->limit(24)
                ->get()
                ->map(fn ($row, $index) => (array)$row + ['class' => $classCycle[$index % count($classCycle)]]);
        } catch (Throwable) {
        }

        return $this->page('pages/home.twig', 'Home', ['top_categories' => $categories]);
    }

    public function recipes(Request $request) {
        return $this->page('recipes/recipes.twig', 'Recipes', ['search' => ['keyword' => $request->query('q', '')]]);
    }

    public function recipe(Recipe $recipe) {
        $this->authorize('view', $recipe);
        $data = $this->recipeData($recipe);

        return $this->page('recipes/recipe-single.twig', $recipe->title, ['recipe' => $data]);
    }

    private function recipeData(Recipe $recipe): array {
        $recipe->load(['chef', 'course', 'cuisine', 'type', 'servingMeasure', 'ingredients.ingredient', 'ingredients.measure', 'children.ingredients.ingredient', 'favoritedBy'])->loadCount('favoritedBy');
        $data = $recipe->toArray();
        $data['recipe_type'] = $recipe->type?->name;
        $data['course'] = $recipe->course?->name;
        $data['cuisine'] = $recipe->cuisine?->name;
        $data['chef'] = $recipe->chef?->name;
        $data['likes_count'] = $recipe->favorited_by_count;
        $data['is_liked'] = request()->user()?->favoriteRecipes()->whereKey($recipe->id)->exists() ?? false;
        $data['i_am_the_chef'] = request()->user() && (request()->user()->is_developer || (int)request()->user()->chef_id === (int)$recipe->chef_id);
        $data['liked_by'] = $recipe->favoritedBy;
        $data['instructions'] = array_values(array_filter(preg_split('/\R/', (string)$recipe->instructions)));
        $data['serving_measure_name'] = $recipe->servingMeasure?->name_plural ?? '';
        $data['ingredient_columns'] = [['items' => $recipe->ingredients->map(fn ($item) => [
            ...$item->toArray(),
            'general_measure_amount' => $item->amount,
            'measure_type_id' => $item->measure?->measure_type_id ?? 0,
            'value_formatted' => rtrim(rtrim(number_format((float)$item->amount, 3, '.', ''), '0'), '.') . ' ' . ($item->measure?->abbr ?? ''),
            'raw_ingredient_name' => $item->ingredient?->name ?? '',
            'raw_ingredient_name_plural' => $item->ingredient?->name_plural ?? '',
            'name_formatted' => ($item->ingredient?->name ?? '') . ($item->ingredient_prep ? '; ' . $item->ingredient_prep : '') . ($item->optional_flag ? ' (optional)' : ''),
        ])->all()]];
        $data['ingredient_col_num'] = 12;
        $data['link'] = route('recipes.show', $recipe);
        $data['children'] = $recipe->children->map(function (Recipe $child) {
            $values = $child->toArray();
            $values['instructions'] = array_values(array_filter(preg_split('/\R/', (string)$child->instructions)));

            return $values;
        })->all();

        return $data;
    }

    public function recipeForm(?Recipe $recipe = null) {
        $recipe ? $this->authorize('update', $recipe) : $this->authorize('create', Recipe::class);

        return $this->page('recipes/recipe-edit.twig', $recipe ? 'Edit Recipe' : 'Create Recipe', ['recipe' => $recipe?->toArray() ?? ['id' => 0], 'chef_id' => request()->user()->chef_id]);
    }

    public function printRecipe(Recipe $recipe) {
        $this->authorize('view', $recipe);

        return $this->page('recipes/recipe-print.twig', $recipe->title, ['recipe' => $this->recipeData($recipe)]);
    }

    public function chef(Chef $chef) {
        $chef->load(['user', 'recipes' => fn ($q) => $q->where('published_flag', true)]);
        $data = $chef->toArray() + [
            'user_id' => $chef->user?->id ?? 0,
            'profile_picture' => $chef->user?->profile_picture ?? '/assets/images/users/generic_avatar.jpg',
            'date_created' => $chef->user?->date_created,
        ];

        return $this->page('pages/chef.twig', $chef->name, ['chef' => $data, 'recipes' => $chef->recipes]);
    }

    public function account(Request $request) {
        $user = $request->user()->load(['chef', 'chefApplication']);
        $application = $user->chefApplication?->toArray() ?: false;
        if (is_array($application)) {
            $application['relationship_name'] = ChefApplication::relationshipName($user->chefApplication->relationship);
        }

        return $this->page('pages/account.twig', 'My Account', [
            'user' => $user->toArray(),
            'chef' => $user->chef?->toArray(), 'chef_app' => $application,
            'goto' => $request->query('goto', ''), 'relations' => $this->relationships(),
        ]);
    }

    public function admin() {
        abort_unless(request()->user()->is_developer, 403);

        return $this->page('pages/admin-nav.twig', 'Admin');
    }

    public function chefApplications() {
        abort_unless(request()->user()->is_developer, 403);

        return $this->page('pages/admin-chef-approval.twig', 'Chef Approval', ['relationships' => $this->relationships()]);
    }

    private function relationships(): array {
        return collect(['birth', 'marriage', 'family', 'other'])
            ->map(fn (string $value) => ['value' => $value, 'name' => ChefApplication::relationshipName($value)])
            ->all();
    }

    private function page(string $template, string $title, array $context = []) {
        return $this->twig->render($template, $context + ['page_title' => $title, 'breadcrumbs' => [['name' => $title, 'link' => request()->url(), 'current_page' => true]]]);
    }
}
