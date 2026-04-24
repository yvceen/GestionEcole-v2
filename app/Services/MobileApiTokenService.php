<?php

namespace App\Services;

use App\Models\PersonalAccessToken;
use App\Models\User;
use Illuminate\Support\Str;

class MobileApiTokenService
{
    public function issueToken(User $user, ?string $deviceName = null): string
    {
        $this->pruneExpiredTokens($user);

        $plainTextToken = Str::random(64);
        $accessToken = $user->tokens()->create([
            'name' => $deviceName ?: 'mobile-app',
            'token' => hash('sha256', $plainTextToken),
            'abilities' => ['mobile'],
            'expires_at' => now()->addMinutes($this->ttlMinutes()),
        ]);

        return $accessToken->id . '|' . $plainTextToken;
    }

    public function revokeAllForUser(User $user): void
    {
        $user->tokens()->delete();
    }

    public function pruneExpiredTokens(?User $user = null): void
    {
        $query = PersonalAccessToken::query()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());

        if ($user instanceof User) {
            $query->where('tokenable_type', $user->getMorphClass())
                ->where('tokenable_id', (int) $user->id);
        }

        $query->delete();
    }

    private function ttlMinutes(): int
    {
        return max(1, (int) config('auth.mobile_api_tokens.ttl_minutes', 43200));
    }
}
