@props([
    'title' => 'Gestion des élèves',
    'subtitle' => '',
    'createRoute' => null,
])

<x-page-header
    :title="$title"
    :subtitle="$subtitle"
    eyebrow="Élèves"
>
    {{ $slot }}

    @if($createRoute)
        <x-ui.button :href="$createRoute" variant="primary" size="lg">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-4 w-4" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
            </svg>
            Nouvel élève
        </x-ui.button>
    @endif
</x-page-header>
