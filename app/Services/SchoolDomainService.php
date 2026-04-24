<?php

namespace App\Services;

use App\Models\School;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SchoolDomainService
{
    private const RESERVED_SUBDOMAINS = [
        'www',
        'admin',
        'api',
        'mail',
        'root',
        'app',
        'login',
    ];

    public function baseDomains(): array
    {
        $configured = collect(explode(',', (string) env('APP_BASE_DOMAINS', 'myedu.school,myedu.test')))
            ->map(fn ($domain) => strtolower(trim($domain)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $configured !== [] ? $configured : ['myedu.school'];
    }

    public function primaryDomain(): string
    {
        $configured = strtolower(trim((string) env('APP_PRIMARY_DOMAIN', '')));
        if ($configured !== '') {
            return $configured;
        }

        $fromConfig = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
        if ($fromConfig !== '' && !in_array($fromConfig, ['127.0.0.1', 'localhost'], true)) {
            return $fromConfig;
        }

        return $this->baseDomains()[0] ?? 'myedu.school';
    }

    public function schoolUrl(string $subdomain): string
    {
        $scheme = strtolower(trim((string) env('APP_SCHOOL_SCHEME', '')));
        if ($scheme === '') {
            $scheme = strtolower((string) parse_url((string) config('app.url'), PHP_URL_SCHEME));
        }

        if ($scheme === '') {
            $scheme = app()->environment('production') ? 'https' : 'http';
        }

        return sprintf('%s://%s.%s', $scheme, $subdomain, $this->primaryDomain());
    }

    public function extractSubdomainFromHost(?string $host): ?string
    {
        $host = strtolower(trim((string) $host));
        if ($host === '' || filter_var($host, FILTER_VALIDATE_IP)) {
            return null;
        }

        foreach ($this->baseDomains() as $baseDomain) {
            if ($host === $baseDomain || $host === 'www.' . $baseDomain) {
                return null;
            }

            $suffix = '.' . $baseDomain;
            if (!Str::endsWith($host, $suffix)) {
                continue;
            }

            $prefix = Str::beforeLast($host, $suffix);
            if ($prefix === '') {
                return null;
            }

            $subdomain = explode('.', $prefix)[0] ?? null;
            $subdomain = strtolower(trim((string) $subdomain));

            return $subdomain !== '' && $subdomain !== 'www' ? $subdomain : null;
        }

        if (in_array($host, ['localhost'], true)) {
            return null;
        }

        $parts = explode('.', $host);
        if (count($parts) >= 3) {
            $subdomain = strtolower(trim((string) ($parts[0] ?? '')));

            return $subdomain !== '' && $subdomain !== 'www' ? $subdomain : null;
        }

        return null;
    }

    public function generateUniqueSubdomain(string $value, ?int $ignoreSchoolId = null): string
    {
        $base = $this->normalizeSubdomain($value);
        if ($base === '') {
            $base = 'school';
        }

        if ($this->isReservedSubdomain($base)) {
            $base .= '-school';
        }

        $candidate = $base;
        $suffix = 2;

        while ($this->subdomainExists($candidate, $ignoreSchoolId)) {
            $candidate = $base . '-' . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    public function normalizeSubdomain(string $value): string
    {
        $normalized = Str::of(Str::ascii($value))
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '-')
            ->replaceMatches('/-+/', '-')
            ->trim('-')
            ->value();

        return $normalized;
    }

    public function isReservedSubdomain(string $value): bool
    {
        return in_array(strtolower(trim($value)), self::RESERVED_SUBDOMAINS, true);
    }

    private function subdomainExists(string $subdomain, ?int $ignoreSchoolId = null): bool
    {
        if (!Schema::hasTable('schools') || !Schema::hasColumn('schools', 'subdomain')) {
            return false;
        }

        return School::query()
            ->when($ignoreSchoolId, fn ($query) => $query->where('id', '<>', $ignoreSchoolId))
            ->where('subdomain', $subdomain)
            ->exists();
    }
}
