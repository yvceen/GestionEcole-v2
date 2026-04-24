@props([
    'title',
    'subtitle' => null,
    'class' => '',
])

<header {{ $attributes->merge(['class' => "mb-8 flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between {$class}"]) }}>
    <div>
        <h1 class="app-title">{{ $title }}</h1>
        @if($subtitle)
            <p class="app-subtitle">{{ $subtitle }}</p>
        @endif
    </div>
    <div class="flex flex-wrap items-center justify-start gap-3 lg:justify-end">
        {{ $actions ?? '' }}
    </div>
</header>
