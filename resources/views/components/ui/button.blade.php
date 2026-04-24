@props([
    'href' => null,
    'variant' => 'primary',
    'type' => 'button',
    'icon' => null,
    'size' => 'md',
    'form' => null,
    'class' => '',
])

@php
    $base = 'app-button-soft';
    $sizes = [
        'sm' => 'min-h-9 rounded-lg px-3 py-2 text-xs',
        'md' => 'min-h-11 px-4 py-2.5 text-sm',
        'lg' => 'min-h-12 px-5 py-3 text-sm',
    ];
    $variants = [
        'primary' => 'app-button-primary',
        'soft' => 'app-button-soft',
        'outline' => 'app-button-outline',
        'secondary' => 'app-button-secondary',
        'ghost' => 'app-button-ghost',
        'danger' => 'app-button-danger',
    ];
    $classes = trim($base.' '.($sizes[$size] ?? $sizes['md']).' '.($variants[$variant] ?? $variants['primary']).' '.$class);
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon)
            <span class="h-4 w-4">{!! $icon !!}</span>
        @endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" @if($form) form="{{ $form }}" @endif {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon)
            <span class="h-4 w-4">{!! $icon !!}</span>
        @endif
        {{ $slot }}
    </button>
@endif
