@php
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;

    $currentSchool = app()->bound('currentSchool')
        ? app('currentSchool')
        : (app()->bound('current_school') ? app('current_school') : null);

    $school = $currentSchool;

    if (!$school) {
        $user = auth()->user();
        $school = $user?->school
            ?: ($user?->school_id ? \App\Models\School::find($user->school_id) : null);
    }

    $defaultIconUrl = asset('images/edulogo.jpg');
    $logoPath = $school?->logo_path;
    $iconUrl = $defaultIconUrl;

    if (is_string($logoPath) && $logoPath !== '') {
        $trimmedLogoPath = ltrim($logoPath, '/');

        if (Str::startsWith($logoPath, ['http://', 'https://'])) {
            $iconUrl = $logoPath;
        } elseif (Storage::disk('public')->exists($trimmedLogoPath)) {
            $iconUrl = asset('storage/' . $trimmedLogoPath);
        }
    }

    $version = $school?->updated_at?->timestamp
        ?? (filled($logoPath) ? crc32((string) $logoPath) : 3);
    $separator = Str::contains($iconUrl, '?') ? '&' : '?';
    $faviconUrl = $iconUrl . $separator . 'v=' . rawurlencode((string) $version);
@endphp

<link rel="icon" sizes="32x32" href="{{ $faviconUrl }}">
<link rel="shortcut icon" href="{{ $faviconUrl }}">
<link rel="apple-touch-icon" href="{{ $faviconUrl }}">
