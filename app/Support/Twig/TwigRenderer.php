<?php

namespace App\Support\Twig;

use App\Support\BuildManifest;
use App\Support\Navigation;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Twig\Environment;

final class TwigRenderer {
    public function __construct(
        private readonly Environment $twig,
        private readonly Navigation  $navigation,
    ) {}

    public function render(string $template, array $context = [], int $status = 200): Response {
        return new Response($this->html($template, $context), $status);
    }

    public function html(string $template, array $context = []): string {
        $user = Auth::user();
        $shared = [
            'current_year' => now()->year,
            'current_user_id' => $user?->id ?? 0,
            'current_user' => $user,
            'is_developer' => (bool)($user?->is_developer ?? false),
            'is_production' => app()->environment('production'),
            'body_class' => '',
            'breadcrumbs' => [],
            'page_title' => 'HuntRecipes',
            'show_footer' => true,
            'hide_call_to_action' => false,
            'main_nav' => $this->navigation->main($user),
            'user_nav' => $this->navigation->user($user),
            'footer_nav' => $this->navigation->footer($user),
            'build_assets' => BuildManifest::assets(),
            'errors' => session('errors'),
            'flash' => session()->all(),
            'firebase' => [
                'api_key' => config('services.firebase.api_key'),
                'auth_domain' => config('services.firebase.auth_domain'),
                'project_id' => config('services.firebase.project_id'),
                'auth_emulator_url' => app()->environment('production') ? null : config('services.firebase.auth_emulator_url'),
            ],
        ];

        return $this->twig->render($template, array_replace($shared, $context));
    }
}
