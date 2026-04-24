@props([
    'title',
    'subtitle' => null,
    'eyebrow' => null,
])

<div class="mb-8 flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
    <div class="min-w-0 space-y-2">
        @if($eyebrow)
            <p class="app-overline">{{ $eyebrow }}</p>
        @endif

        <div class="space-y-1">
            <h1 class="truncate text-[1.8rem] font-semibold tracking-tight text-slate-950">{{ $title }}</h1>
        @if($subtitle)
                <p class="max-w-3xl text-sm leading-6 text-slate-500">{{ $subtitle }}</p>
            @endif
        </div>
    </div>

    @if (trim($slot))
        <div class="flex flex-wrap items-center gap-3 justify-start lg:justify-end">
            {{ $slot }}
        </div>
    @endif
</div>
