@props([
    'label',
    'value',
    'meta' => null,
    'tone' => 'slate',
])

@php
    $toneClasses = [
        'slate' => 'super-stat-slate',
        'sky' => 'super-stat-sky',
        'emerald' => 'super-stat-emerald',
        'amber' => 'super-stat-amber',
        'rose' => 'super-stat-rose',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'super-stat-card '.($toneClasses[$tone] ?? $toneClasses['slate'])]) }}>
    <div class="flex items-start justify-between gap-4">
        <div>
            <p class="super-stat-label">{{ $label }}</p>
            <p class="super-stat-value">{{ $value }}</p>
        </div>

        @if(trim((string) $slot) !== '')
            <div class="super-stat-icon">
                {{ $slot }}
            </div>
        @endif
    </div>

    @if($meta)
        <p class="super-stat-meta">{{ $meta }}</p>
    @endif
</div>
