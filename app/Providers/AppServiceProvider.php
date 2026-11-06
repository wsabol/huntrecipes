<?php

namespace App\Providers;

use App\Models\User;
use App\Observers\UserObserver;
use App\Support\Twig\HuntRecipesExtension;
use App\Support\Twig\TwigRenderer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use RuntimeException;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class AppServiceProvider extends ServiceProvider {
    /**
     * Register any application services.
     */
    public function register(): void {
        $this->app->singleton(Environment::class, function ($app): Environment {
            $loader = new FilesystemLoader(resource_path('views'));
            $twig = new Environment($loader, [
                'cache' => $app->environment('testing') ? false : storage_path('framework/views/twig'),
                'auto_reload' => true,
                // The preserved legacy templates frequently treat absent optional values as null.
                'strict_variables' => false,
            ]);
            $twig->addExtension($app->make(HuntRecipesExtension::class));

            return $twig;
        });

        $this->app->singleton(TwigRenderer::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void {
        // The Firebase SDK skips token signature verification whenever an
        // emulator host is configured, which would let anyone forge a session.
        if ($this->app->environment('production') && $this->firebaseAuthEmulatorHost() !== null) {
            throw new RuntimeException('FIREBASE_AUTH_EMULATOR_HOST must not be set in production.');
        }

        // Build absolute URLs (share meta, canonical links, emails) from APP_URL
        // rather than the request, which may arrive as plain http behind a proxy.
        if ($this->app->environment('production')) {
            URL::useOrigin(config('app.url'));
            if (str_starts_with(config('app.url'), 'https://')) {
                URL::forceScheme('https');
            }
        }

        User::observe(UserObserver::class);
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by(
            (string)($request->user()?->id ?? $request->ip())
        ));
    }

    /**
     * Mirrors how the Firebase SDK detects the auth emulator, which reads the
     * raw process environment rather than Laravel's config.
     */
    private function firebaseAuthEmulatorHost(): ?string {
        $name = 'FIREBASE_AUTH_EMULATOR_HOST';
        $value = trim((string)($_SERVER[$name] ?? $_ENV[$name] ?? getenv($name)));

        return $value !== '' ? $value : null;
    }
}
