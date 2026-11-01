<?php

namespace App\Providers;

use App\Models\User;
use App\Observers\UserObserver;
use App\Support\Twig\HuntRecipesExtension;
use App\Support\Twig\TwigRenderer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Kreait\Firebase\Util;
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
        if ($this->app->environment('production') && Util::authEmulatorHost() !== null) {
            throw new RuntimeException('FIREBASE_AUTH_EMULATOR_HOST must not be set in production.');
        }

        User::observe(UserObserver::class);
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by(
            (string)($request->user()?->id ?? $request->ip())
        ));
    }
}
