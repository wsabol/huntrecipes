<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateFirebaseSessionRequest;
use App\Http\Resources\UserResource;
use App\Services\FirebaseIdentity;
use DateTimeInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Cookie;

class AuthSessionController extends Controller {
    public function store(CreateFirebaseSessionRequest $request, FirebaseIdentity $identity): JsonResponse {
        $token = $identity->verifyIdToken($request->string('id_token')->toString());
        $authTime = $token->claims()->get('auth_time');
        $authTimestamp = $authTime instanceof DateTimeInterface ? $authTime->getTimestamp() : (int)$authTime;
        abort_if($authTimestamp < now()->subMinutes(5)->timestamp, 401, 'Recent authentication is required.');
        abort_unless((bool)$token->claims()->get('email_verified', false), 403, 'A verified email is required.');
        $user = $identity->resolveLocalUser($token);
        abort_unless($user->isEnabled(), 403, 'Account disabled.');
        $remember = $request->boolean('remember');
        $cookie = Cookie::create(config('services.firebase.session_cookie', '__session'))
            ->withValue($identity->createSessionCookie($request->string('id_token')->toString(), $remember))
            ->withExpires($remember ? now()->addDays(14) : 0)->withPath('/')->withSecure(app()->environment('production'))->withHttpOnly(true)->withSameSite('lax');
        Log::info('Firebase session established.', ['user_id' => $user->id, 'remember' => $remember]);

        return (new UserResource($user))->response()->setStatusCode(200)->withCookie($cookie);
    }

    public function destroy(Request $request): JsonResponse {
        return response()->json(['message' => 'Signed out.'])->withoutCookie(config('services.firebase.session_cookie', '__session'));
    }
}
