@props([
    'title' => null,
    'subtitle' => null,
    'padding' => 'p-6',
    'class' => '',
])

<section {{ $attributes->merge(['class' => "super-panel {$padding} {$class}"]) }}>
    @if($title || $subtitle || isset($actions))
        <header class="flex flex-col gap-4 border-b border-slate-200/80 pb-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                @if($title)
                    <h2 class="text-base font-semibold text-slate-950">{{ $title }}</h2>
                @endif

                @if($subtitle)
                    <p class="mt-1 text-sm leading-6 text-slate-500">{{ $subtitle }}</p>
                @endif
            </div>

            @if(isset($actions))
                <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                    {{ $actions }}
                </div>
            @endif
        </header>
    @endif

    <div @class(['space-y-5' => $title || $subtitle || isset($actions)])>
        {{ $slot }}
    </div>
</section>
