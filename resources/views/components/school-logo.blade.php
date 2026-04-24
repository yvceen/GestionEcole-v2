@props([
    'size' => 48,
])

@php
    $school = auth()->user()?->school;
    if (!$school && app()->bound('current_school')) {
        $school = app('current_school');
    }
    $logoPath = $school?->logo_path;
    $defaultLogoUrl = asset('images/edulogo.jpg') . '?v=3';
    $logoUrl = $logoPath && \Illuminate\Support\Facades\Storage::disk('public')->exists(ltrim($logoPath, '/'))
        ? asset('storage/' . ltrim($logoPath, '/'))
        : $defaultLogoUrl;
@endphp

<div {{ $attributes->merge(['class' => 'edu-logo-circle']) }} style="width: {{ (int) $size }}px; height: {{ (int) $size }}px;">
    <img src="{{ $logoUrl }}" alt="My Edu logo" onerror="this.onerror=null; this.src='{{ $defaultLogoUrl }}';">
</div>
