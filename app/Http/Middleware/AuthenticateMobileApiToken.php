<?php

namespace App\Http\Middleware;

use App\Models\PersonalAccessToken;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateMobileApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $bearerToken = (string) $request->bearerToken();
        if ($bearerToken === '') {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        [$tokenId, $plainTextToken] = array_pad(explode('|', $bearerToken, 2), 2, null);
        if (!is_numeric($tokenId) || empty($plainTextToken)) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $accessToken = PersonalAccessToken::query()->find((int) $tokenId);
        if (!$accessToken) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $expectedHash = hash('sha256', (string) $plainTextToken);
        if (!hash_equals((string) $accessToken->token, $expectedHash)) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ($accessToken->expires_at && $accessToken->expires_at->isPast()) {
            return response()->json([
                'message' => 'Token expired.',
            ], 401);
        }

        $tokenable = $accessToken->tokenable;
        if (!$tokenable instanceof User) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $accessToken->forceFill([
            'last_used_at' => now(),
        ])->save();

        Auth::setUser($tokenable);
        $request->setUserResolver(static fn (): User => $tokenable);
        $request->attributes->set('current_api_token', $accessToken);

        /** @var Response $response */
        $response = $next($request);

        return $response;
    }
}
