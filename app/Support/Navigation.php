<?php

namespace App\Support;

use App\Models\User;

final class Navigation {
    public function main(?User $user): array {
        $items = [
            $this->item('Home', 'home'),
            $this->item('About', 'about'),
            $this->item('Recipes', 'recipes.index'),
            $this->item('Contact', 'contact'),
        ];

        if ($user?->is_developer) {
            $items[] = array_merge($this->item('Admin', 'admin.index'), [
                'submenu' => [['title' => 'Chef Approval', 'href' => route('admin.chef-applications')]],
            ]);
        }

        return $items;
    }

    public function user(?User $user): array {
        if (!$user) {
            return [['title' => 'Sign in', 'a_href' => route('sign-in'), 'icon' => 'fa-solid fa-arrow-right-to-bracket', 'li_class' => 'light']];
        }

        $items = [
            ['title' => 'Favorites', 'a_href' => route('account', ['goto' => 'favorites']), 'icon' => 'fa fa-heart', 'li_class' => 'light'],
            ['title' => 'Account', 'a_href' => route('account'), 'icon' => 'fa fa-user', 'li_class' => 'medium'],
        ];
        if ($user->is_chef) {
            $items[] = ['title' => 'Submit a recipe', 'a_href' => route('recipes.create'), 'icon' => 'icon-themeenergy_fork-spoon', 'li_class' => 'dark'];
        }

        return $items;
    }

    public function footer(?User $user): array {
        $items = [
            ['title' => 'Home', 'a_href' => route('home')],
            ['title' => 'About', 'a_href' => route('about')],
            ['title' => 'Recipes', 'a_href' => route('recipes.index')],
            ['title' => 'Contact', 'a_href' => route('contact')],
            ['title' => 'Privacy', 'a_href' => route('privacy')],
        ];
        $items[] = ['title' => $user ? 'Account' : 'Sign In', 'a_href' => $user ? route('account') : route('sign-in')];

        return $items;
    }

    private function item(string $title, string $route): array {
        return [
            'title' => $title,
            'a_href' => route($route),
            'is_active' => request()->routeIs($route),
        ];
    }
}
