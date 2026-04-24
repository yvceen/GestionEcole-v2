@props([
    'title' => null,
    'subtitle' => null,
    'padding' => 'p-6',
    'class' => '',
])

<section {{ $attributes->merge(['class' => "app-card rounded-[24px] {$padding} {$class}"]) }}>
    @if($title || $subtitle)
        <header class="mb-5 space-y-1">
            @if($title)
                <h2 class="text-lg font-semibold text-slate-900">{{ $title }}</h2>
            @endif
            @if($subtitle)
                <p class="text-sm text-slate-500">{{ $subtitle }}</p>
            @endif
        </header>
    @endif
    {{ $slot }}
</section>
