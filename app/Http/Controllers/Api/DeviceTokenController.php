<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();
        abort_unless($user, 401);

        $data = $request->validate([
            'token' => ['required', 'string', 'max:4096'],
            'platform' => ['nullable', 'in:' . implode(',', DeviceToken::platforms())],
        ]);
        $normalizedToken = trim((string) $data['token']);
        abort_if($normalizedToken === '', 422, 'Token invalide.');

        $schoolId = app()->bound('current_school_id')
            ? (int) app('current_school_id')
            : (int) ($user->school_id ?? 0);

        $record = DeviceToken::query()->updateOrCreate(
            ['token_hash' => hash('sha256', $normalizedToken)],
            [
                'user_id' => (int) $user->id,
                'school_id' => $schoolId > 0 ? $schoolId : null,
                'platform' => $data['platform'] ?? DeviceToken::PLATFORM_ANDROID,
                'token' => $normalizedToken,
                'last_used_at' => now(),
            ]
        );

        return response()->json([
            'ok' => true,
            'platform' => (string) $record->platform,
            'last_used_at' => optional($record->last_used_at)->toIso8601String(),
        ]);
    }
}
