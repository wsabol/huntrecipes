<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends the Content-Security-Policy header.
 *
 * This lived in public/.htaccess, which only works when Apache serves the request
 * and fails silently otherwise -- behind nginx or a proxy the app would run with no
 * policy at all and nothing would say so. Here it travels with the application and
 * the test suite can assert on it.
 */
class ContentSecurityPolicy {
    /**
     * Only directives that are actually needed, because every source listed is a
     * host that gets to run or style something. Note that frame-ancestors,
     * form-action and base-uri do not fall back to default-src -- omitting them
     * means no protection rather than inheriting 'self'.
     *
     * @var array<string, list<string>>
     */
    protected const POLICY = [
        'default-src' => ["'self'"],

        // No inline scripts anywhere in the templates, so no 'unsafe-inline' and no
        // nonce plumbing. Keep it that way: put page behaviour in resources/js.
        'script-src' => ["'self'", 'https://apis.google.com', 'https://cdnjs.buymeacoffee.com'],

        // 'unsafe-inline' is still required by the remaining style="..." attributes in
        // Twig templates; nonces do not apply to style attributes.
        'style-src' => ["'self'", "'unsafe-inline'", 'https://fonts.googleapis.com'],

        // The donation button loads its Cookie font from Google Fonts.
        'font-src' => ["'self'", 'https://fonts.gstatic.com'],

        // Google profile pictures are served from lh3-lh6, whichever the account
        // happens to use, so match the whole host rather than one subdomain.
        'img-src' => ["'self'", 'data:', 'blob:', 'https://googleusercontent.com', 'https://*.googleusercontent.com'],

        'connect-src' => ["'self'", 'https://identitytoolkit.googleapis.com', 'https://securetoken.googleapis.com'],
        'frame-src' => ["'self'"],

        // /serviceworker.js. Explicit because worker-src otherwise falls back
        // through child-src to script-src, which is easy to break by accident.
        'worker-src' => ["'self'"],

        'frame-ancestors' => ["'none'"],

        // Firebase handles Google sign-in through a popup, not a form redirect.
        'form-action' => ["'self'"],

        'base-uri' => ["'self'"],
        'object-src' => ["'none'"],
    ];

    public function handle(Request $request, Closure $next): Response {
        $response = $next($request);

        $response->headers->set('Content-Security-Policy', $this->policy($request));

        return $response;
    }

    /**
     * The header value, e.g. "default-src 'self'; script-src 'self'; ...".
     */
    public function policy(?Request $request = null): string {
        $policy = self::POLICY;
        $authDomain = config('services.firebase.auth_domain');
        if (is_string($authDomain) && preg_match('/\A[a-z0-9.-]+(?::\d+)?\z/i', $authDomain)) {
            $policy['frame-src'][] = 'https://' . $authDomain;
            $policy['connect-src'][] = 'https://' . $authDomain;
        }

        $directives = [];

        foreach ($policy as $directive => $sources) {
            $directives[] = $directive . ' ' . implode(' ', $sources);
        }

        return implode('; ', $directives);
    }
}
