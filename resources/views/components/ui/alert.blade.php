@props([
    'variant' => 'info',
])

@php
    $styles = match ($variant) {
        'success' => 'app-alert-success',
        'error' => 'app-alert-error',
        'warning' => 'app-alert-warning',
        default => 'app-alert-info',
    };
@endphp

<div {{ $attributes->merge(['class' => "app-alert {$styles}"]) }}>
    {{ $slot }}
</div>
