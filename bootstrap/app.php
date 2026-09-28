<?php

use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\ResolveFirebaseUser;
use App\Support\Twig\TwigRenderer;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(AddSecurityHeaders::class);
        $middleware->alias([
            'firebase' => ResolveFirebaseUser::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (NotFoundHttpException $exception, Request $request) {
            if (!$request->is('api/*')) {
                return null;
            }

            return response()->json([
                'message' => 'Resource not found.',
                'data' => ['uri' => $request->getUri()],
            ], 404);
        });

        $exceptions->render(function (NotFoundHttpException $exception, Request $request) {
            if ($request->expectsJson()) {
                return null;
            }

            return app(TwigRenderer::class)->render('errors/404.twig', ['page_title' => 'Page Not Found'], 404);
        });
    })->create();
