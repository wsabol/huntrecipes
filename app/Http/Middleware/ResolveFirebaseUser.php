<?php

namespace App\Http\Middleware;

use App\Services\FirebaseIdentity;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class ResolveFirebaseUser {
    public function handle(Request $request, Closure $next, string $required = 'optional'): Response {
        $name = (string)config('services.firebase.session_cookie', '__session');
        $cookie = $request->cookie($name);
        $invalidCookie = false;
        if ($cookie) {
            try {
                $identity = app(FirebaseIdentity::class);
                $user = $identity->resolveLocalUser($identity->verifySessionCookie($cookie));
                if (!$user->isEnabled()) {
                    throw new RuntimeException('Account disabled.');
                }
                Auth::setUser($user);
                $request->setUserResolver(fn () => $user);
            } catch (Throwable $exception) {
                report($exception);
                $invalidCookie = true;
            }
        }

        $response = $required === 'required' && !$request->user()
            ? ($request->expectsJson()
                ? response()->json(['message' => 'Unauthenticated.'], 401)
                : redirect()->route('sign-in', ['return' => $request->getRequestUri()]))
            : $next($request);

        if ($invalidCookie) {
            $response->headers->clearCookie($name, '/');
        }

        return $response;
    }
}
