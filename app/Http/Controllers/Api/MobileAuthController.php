<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PersonalAccessToken;
use App\Models\School;
use App\Models\User;
use App\Services\MobileApiTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class MobileAuthController extends Controller
{
    public function __construct(
        private readonly MobileApiTokenService $mobileApiTokenService,
    ) {
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        $throttleKey = Str::transliterate(Str::lower($data['email']) . '|' . $request->ip());
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return response()->json([
                'message' => trans('auth.throttle', [
                    'seconds' => RateLimiter::availableIn($throttleKey),
                    'minutes' => ceil(RateLimiter::availableIn($throttleKey) / 60),
                ]),
            ], 429);
        }

        $user = User::query()->where('email', $data['email'])->first();
        if (!$user || !Hash::check($data['password'], (string) $user->password)) {
            RateLimiter::hit($throttleKey, 60);

            return response()->json([
                'message' => trans('auth.failed'),
            ], 422);
        }

        if ((string) $user->role !== User::ROLE_SUPER_ADMIN && !(bool) ($user->is_active ?? false)) {
            RateLimiter::hit($throttleKey, 60);

            return response()->json([
                'message' => 'This account is disabled.',
            ], 403);
        }

        $hostSchool = $request->attributes->get('school_from_subdomain');
        if ($hostSchool && (string) $user->role !== User::ROLE_SUPER_ADMIN) {
            if ((int) $user->school_id !== (int) $hostSchool->id) {
                return response()->json([
                    'message' => 'This account is not allowed for the current school.',
                ], 403);
            }
        }

        if ((string) $user->role !== User::ROLE_SUPER_ADMIN && !empty($user->school_id)) {
            $school = School::query()->find((int) $user->school_id);
            if ($school && !$school->is_active) {
                return response()->json([
                    'message' => 'Your school is inactive.',
                ], 403);
            }
        }

        RateLimiter::clear($throttleKey);

        $token = $this->mobileApiTokenService->issueToken($user, $data['device_name'] ?? 'mobile-app');

        return response()->json([
            'message' => 'Login successful.',
            'token_type' => 'Bearer',
            'token' => $token,
            'role' => (string) $user->role,
            'school_id' => $user->school_id ? (int) $user->school_id : null,
            'user' => $this->userPayload($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var PersonalAccessToken|null $token */
        $token = $request->attributes->get('current_api_token');
        if ($token) {
            $token->delete();
        }

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'role' => (string) $user->role,
            'school_id' => $user->school_id ? (int) $user->school_id : null,
            'user' => $this->userPayload($user),
        ]);
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => (int) $user->id,
            'name' => (string) $user->name,
            'email' => (string) $user->email,
            'phone' => $user->phone,
            'role' => (string) $user->role,
            'role_label' => $this->mobileRoleLabel((string) $user->role),
            'school_id' => $user->school_id ? (int) $user->school_id : null,
        ];
    }

    private function mobileRoleLabel(string $role): string
    {
        return match ($role) {
            User::ROLE_SUPER_ADMIN => 'Super Admin',
            User::ROLE_ADMIN => 'Admin',
            User::ROLE_DIRECTOR => 'Director',
            User::ROLE_TEACHER => 'Teacher',
            User::ROLE_PARENT => 'Parent',
            User::ROLE_STUDENT => 'Student',
            User::ROLE_SCHOOL_LIFE => 'School Life',
            User::ROLE_CHAUFFEUR => 'Driver',
            default => ucfirst(str_replace('_', ' ', $role)),
        };
    }
}
