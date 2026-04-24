<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MobileApiTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class MobilePasswordController extends Controller
{
    public function __construct(
        private readonly MobileApiTokenService $mobileApiTokenService,
    ) {
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);
        $this->mobileApiTokenService->revokeAllForUser($user);

        return response()->json([
            'message' => 'Password updated successfully.',
        ]);
    }
}
