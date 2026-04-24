@props([
    'title' => 'Aucun résultat',
    'description' => null,
])

<div class="rounded-[24px] border border-slate-200 bg-white/90 p-6 shadow-sm text-slate-600">
    <div class="text-base font-semibold text-slate-900">{{ $title }}</div>
    @if($description)
        <div class="mt-2 text-sm leading-6 text-slate-500">{{ $description }}</div>
    @endif
    @if(trim($slot) !== '')
        <div class="mt-5">
            {{ $slot }}
        </div>
    @endif
</div>
