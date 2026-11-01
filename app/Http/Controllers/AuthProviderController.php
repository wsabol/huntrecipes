<?php

namespace App\Http\Controllers;

use App\Exceptions\InactiveFirebaseIdentity;
use App\Services\FirebaseIdentity;
use DateTimeInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;

final class AuthProviderController extends Controller {
    public function index(Request $request, FirebaseIdentity $identity): JsonResponse {
        $firebaseUser = $identity->firebaseUser($request->user()->firebase_uid);

        return response()->json(['data' => [
            'providers' => collect($firebaseUser->providerData)->pluck('providerId')->values(),
        ]]);
    }

    public function destroyGoogle(Request $request, FirebaseIdentity $identity): JsonResponse {
        $data = $request->validate(['id_token' => ['required', 'string', 'max:8192']]);
        try {
            $token = $identity->verifyIdToken($data['id_token']);
        } catch (InactiveFirebaseIdentity|FailedToVerifyToken) {
            abort(401, 'Sign-in could not be verified.');
        }
        $authTime = $token->claims()->get('auth_time');
        $authTimestamp = $authTime instanceof DateTimeInterface ? $authTime->getTimestamp() : (int)$authTime;
        abort_if($authTimestamp < now()->subMinutes(5)->timestamp, 401, 'Recent authentication is required.');
        abort_unless((string)$token->claims()->get('sub') === $request->user()->firebase_uid, 403);

        $firebaseUser = $identity->firebaseUser($request->user()->firebase_uid);
        $providers = collect($firebaseUser->providerData)->pluck('providerId');
        abort_unless($providers->contains('google.com'), 422, 'Google is not linked to this account.');
        abort_if($providers->count() < 2, 422, 'At least one sign-in provider must remain linked.');

        $identity->unlinkProvider($request->user()->firebase_uid, 'google.com');
        $identity->revokeRefreshTokens($request->user()->firebase_uid);

        return response()
            ->json(['message' => 'Google was unlinked. Sign in again to continue.'])
            ->withoutCookie(config('services.firebase.session_cookie', '__session'));
    }
}
