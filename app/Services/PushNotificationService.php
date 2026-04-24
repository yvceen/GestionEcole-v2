<?php

namespace App\Services;

use App\Models\DeviceToken;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    public function sendToUsers(array $userIds, string $title, string $body, array $data = [], ?int $schoolId = null): int
    {
        $ids = collect($userIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return 0;
        }

        $tokens = DeviceToken::query()
            ->whereIn('user_id', $ids)
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
            ->whereIn('platform', [
                DeviceToken::PLATFORM_ANDROID,
                DeviceToken::PLATFORM_IOS,
            ])
            ->pluck('token')
            ->map(fn ($token) => trim((string) $token))
            ->filter()
            ->unique()
            ->values();

        if ($tokens->isEmpty()) {
            return 0;
        }

        $sent = 0;
        foreach ($tokens as $token) {
            if ($this->sendToToken($token, $title, $body, $data)) {
                $sent++;
            }
        }

        return $sent;
    }

    public function sendToToken(string $token, string $title, string $body, array $data = []): bool
    {
        $token = trim($token);
        if ($token === '') {
            return false;
        }

        if ($this->hasServiceAccountConfig()) {
            return $this->sendViaHttpV1($token, $title, $body, $data);
        }

        return $this->sendViaLegacyServerKey($token, $title, $body, $data);
    }

    private function sendViaHttpV1(string $token, string $title, string $body, array $data): bool
    {
        $projectId = (string) config('services.fcm.project_id');
        $accessToken = $this->googleAccessToken();
        if ($accessToken === null || $projectId === '') {
            return false;
        }

        $payload = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $this->stringifyData($data),
                'android' => [
                    'priority' => 'high',
                ],
                'apns' => [
                    'headers' => [
                        'apns-priority' => '10',
                    ],
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                        ],
                    ],
                ],
            ],
        ];

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", $payload);

        if ($response->successful()) {
            return true;
        }

        if ($this->isInvalidTokenResponse($response->status(), (string) $response->body())) {
            $this->purgeInvalidToken($token);
        }

        Log::warning('FCM v1 push failed', [
            'status' => $response->status(),
            'response' => $response->body(),
        ]);

        return false;
    }

    private function sendViaLegacyServerKey(string $token, string $title, string $body, array $data): bool
    {
        $serverKey = trim((string) config('services.fcm.server_key'));
        if ($serverKey === '') {
            return false;
        }

        $payload = [
            'to' => $token,
            'priority' => 'high',
            'notification' => [
                'title' => $title,
                'body' => $body,
                'sound' => 'default',
            ],
            'data' => $this->stringifyData($data),
        ];

        $response = Http::withHeaders([
            'Authorization' => 'key=' . $serverKey,
            'Content-Type' => 'application/json',
        ])->post('https://fcm.googleapis.com/fcm/send', $payload);

        if ($response->successful()) {
            $json = $response->json();
            $failure = (int) ($json['failure'] ?? 0);
            if ($failure <= 0) {
                return true;
            }

            $results = (array) ($json['results'] ?? []);
            foreach ($results as $result) {
                $error = strtolower((string) ($result['error'] ?? ''));
                if (str_contains($error, 'notregistered') || str_contains($error, 'invalidregistration')) {
                    $this->purgeInvalidToken($token);
                }
            }
        }

        Log::warning('FCM legacy push failed', [
            'status' => $response->status(),
            'response' => $response->body(),
        ]);

        return false;
    }

    private function hasServiceAccountConfig(): bool
    {
        return trim((string) config('services.fcm.project_id')) !== ''
            && trim((string) config('services.fcm.client_email')) !== ''
            && trim((string) config('services.fcm.private_key')) !== '';
    }

    private function googleAccessToken(): ?string
    {
        return Cache::remember('fcm_google_access_token', now()->addMinutes(50), function () {
            $jwt = $this->buildServiceAccountJwt();
            if ($jwt === null) {
                return null;
            }

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if (!$response->successful()) {
                Log::warning('Unable to fetch Google OAuth token for FCM', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                return null;
            }

            return (string) ($response->json()['access_token'] ?? '');
        });
    }

    private function buildServiceAccountJwt(): ?string
    {
        $privateKey = trim((string) config('services.fcm.private_key'));
        $clientEmail = trim((string) config('services.fcm.client_email'));
        if ($privateKey === '' || $clientEmail === '') {
            return null;
        }

        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $now = time();
        $claims = [
            'iss' => $clientEmail,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ];

        $encodedHeader = $this->base64UrlEncode(json_encode($header, JSON_UNESCAPED_SLASHES));
        $encodedClaims = $this->base64UrlEncode(json_encode($claims, JSON_UNESCAPED_SLASHES));
        $signatureInput = $encodedHeader . '.' . $encodedClaims;

        $signature = '';
        $key = openssl_pkey_get_private(str_replace('\n', "\n", $privateKey));
        if (!$key || !openssl_sign($signatureInput, $signature, $key, OPENSSL_ALGO_SHA256)) {
            Log::warning('Unable to sign JWT for FCM service account');

            return null;
        }

        return $signatureInput . '.' . $this->base64UrlEncode($signature);
    }

    private function stringifyData(array $data): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            if ($value === null) {
                continue;
            }
            $out[(string) $key] = is_scalar($value) ? (string) $value : json_encode($value);
        }

        return $out;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function isInvalidTokenResponse(int $status, string $body): bool
    {
        if ($status === 404 || $status === 410) {
            return true;
        }

        $lower = strtolower($body);

        return str_contains($lower, 'registration-token-not-registered')
            || str_contains($lower, 'invalid registration token')
            || str_contains($lower, 'notregistered');
    }

    private function purgeInvalidToken(string $token): void
    {
        $hash = hash('sha256', $token);

        DeviceToken::query()
            ->where('token_hash', $hash)
            ->orWhere('token', $token)
            ->delete();
    }
}
