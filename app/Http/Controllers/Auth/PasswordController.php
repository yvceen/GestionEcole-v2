<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\MobileApiTokenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    public function __construct(
        private readonly MobileApiTokenService $mobileApiTokenService,
    ) {
    }

    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);
        $this->mobileApiTokenService->revokeAllForUser($user);

        return back()->with('status', 'password-updated');
    }
}
