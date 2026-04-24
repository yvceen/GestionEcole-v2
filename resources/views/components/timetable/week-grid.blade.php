@props([
    'days' => [],
    'slotsByDay' => collect(),
    'editable' => false,
    'editRouteName' => null,
    'deleteRouteName' => null,
])

<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
    @foreach($days as $dayNumber => $dayLabel)
        @php $daySlots = $slotsByDay->get($dayNumber, collect()); @endphp
        <section class="app-card overflow-hidden">
            <header class="border-b border-slate-200 bg-blue-50/70 px-4 py-3">
                <h3 class="text-sm font-semibold text-slate-700">{{ $dayLabel }}</h3>
            </header>

            <div class="space-y-3 p-4">
                @forelse($daySlots as $slot)
                    <article class="rounded-xl border border-blue-100 bg-blue-50/40 p-3">
                        <div class="flex items-start justify-between gap-2">
                            <p class="text-sm font-semibold text-slate-900">{{ $slot->subject }}</p>
                            <span class="app-badge app-badge-info">{{ substr((string) $slot->start_time, 0, 5) }} - {{ substr((string) $slot->end_time, 0, 5) }}</span>
                        </div>
                        <p class="mt-1 text-xs text-slate-600">
                            Enseignant: {{ $slot->teacher?->name ?? 'Non renseigne' }}
                        </p>
                        <p class="text-xs text-slate-600">
                            Salle: {{ $slot->room ?: 'Non renseignee' }}
                        </p>

                        @if($editable && $editRouteName && $deleteRouteName)
                            <div class="mt-3 flex items-center gap-2">
                                <a href="{{ route($editRouteName, $slot) }}" class="app-button-secondary px-3 py-1.5 text-xs">
                                    Modifier
                                </a>
                                <form method="POST" action="{{ route($deleteRouteName, $slot) }}" onsubmit="return confirm('Supprimer ce creneau ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100">
                                        Supprimer
                                    </button>
                                </form>
                            </div>
                        @endif
                    </article>
                @empty
                    <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 p-4 text-xs text-slate-500">
                        Aucun creneau pour ce jour.
                    </div>
                @endforelse
            </div>
        </section>
    @endforeach
</div>

