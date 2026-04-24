@props([
    'name',
    'title' => 'Confirmation',
    'description' => null,
])

<div
    x-data="{ open: false }"
    x-on:open-modal.window="if ($event.detail === '{{ $name }}') open = true"
    x-on:close-modal.window="if ($event.detail === '{{ $name }}') open = false"
    x-on:keydown.escape.window="open = false"
>
    {{ $trigger ?? '' }}

    <div
        x-show="open"
        x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
        style="display: none;"
    >
        <div x-on:click.outside="open = false" class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-xl">
            <h3 class="text-lg font-semibold text-slate-900">{{ $title }}</h3>
            @if($description)
                <p class="mt-2 text-sm text-slate-600">{{ $description }}</p>
            @endif
            <div class="mt-6">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
