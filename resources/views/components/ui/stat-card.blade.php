@props([
    'label',
    'value',
    'icon' => null,
    'tone' => 'slate',
    'description' => null,
])

@php
    $tones = [
        'slate' => 'from-slate-50 via-white to-slate-100 border-slate-200/70 text-slate-700',
        'indigo' => 'from-indigo-50 via-white to-indigo-100 border-indigo-200/70 text-indigo-700',
        'emerald' => 'from-emerald-50 via-white to-emerald-100 border-emerald-200/70 text-emerald-700',
        'rose' => 'from-rose-50 via-white to-rose-100 border-rose-200/70 text-rose-700',
        'blue' => 'from-blue-50 via-white to-blue-100 border-blue-200/70 text-blue-700',
    ];
    $toneClass = $tones[$tone] ?? $tones['slate'];
@endphp

<div class="rounded-3xl border bg-gradient-to-br p-5 shadow-sm {{ $toneClass }}">
    <div class="flex items-center justify-between">
        <div class="text-xs font-semibold uppercase tracking-wide">{{ $label }}</div>
        @if($icon)
            <div class="rounded-2xl bg-white/70 p-2 text-sm">
                {{ $icon }}
            </div>
        @endif
    </div>
    <div class="mt-4 text-3xl font-semibold text-slate-900">{{ $value }}</div>
    @if($description)
        <div class="text-xs text-slate-500">{{ $description }}</div>
    @endif
</div>
