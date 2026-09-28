<?php

namespace App\Support\Twig;

use Illuminate\Support\Facades\Vite;
use Throwable;
use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFunction;

final class HuntRecipesExtension extends AbstractExtension {
    public function getFunctions(): array {
        return [
            new TwigFunction('route', fn(string $name, mixed $parameters = []) => route($name, $parameters)),
            new TwigFunction('url', fn(string $path = '') => url($path)),
            new TwigFunction('asset', fn(string $path) => asset($path)),
            new TwigFunction('csrf_token', fn() => csrf_token()),
            new TwigFunction('csrf_field', fn() => new Markup(csrf_field(), 'UTF-8'), ['is_safe' => ['html']]),
            new TwigFunction('old', fn(string $key, mixed $default = null) => old($key, $default)),
            new TwigFunction('vite_tags', fn() => $this->viteTags(), ['is_safe' => ['html']]),
        ];
    }

    private function viteTags(): Markup {
        try {
            return new Markup(Vite::withEntryPoints([
                'resources/scss/app.scss',
                'resources/js/app.js',
            ])->toHtml(), 'UTF-8');
        } catch (Throwable) {
            return new Markup('<!-- Run npm run build to publish frontend assets. -->', 'UTF-8');
        }
    }
}
