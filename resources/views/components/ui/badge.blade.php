@props([
    'variant' => 'info',
])

@php
    $variantClass = match ($variant) {
        'success' => 'app-badge-success',
        'warning' => 'app-badge-warning',
        'danger' => 'app-badge-danger',
        default => 'app-badge-info',
    };
@endphp

<span {{ $attributes->merge(['class' => "app-badge {$variantClass}"]) }}>
    {{ $slot }}
</span>
