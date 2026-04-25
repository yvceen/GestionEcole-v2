@php
    $showManageActions = $showManageActions ?? false;
@endphp

<section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <article class="app-stat-card">
        <p class="app-stat-label">Evenements</p>
        <p class="app-stat-value">{{ $summary['total'] ?? 0 }}</p>
        <p class="app-stat-meta">Total affiche pour le mois choisi.</p>
    </article>
    <article class="app-stat-card">
        <p class="app-stat-label">Examens</p>
        <p class="app-stat-value text-amber-700">{{ $summary['exam'] ?? 0 }}</p>
        <p class="app-stat-meta">Evaluations et temps forts pedagogiques.</p>
    </article>
    <article class="app-stat-card">
        <p class="app-stat-label">Vacances</p>
        <p class="app-stat-value text-emerald-700">{{ $summary['holiday'] ?? 0 }}</p>
        <p class="app-stat-meta">Fermetures et conges scolaires.</p>
    </article>
    <article class="app-stat-card">
        <p class="app-stat-label">Evenements</p>
        <p class="app-stat-value text-sky-700">{{ $summary['event'] ?? 0 }}</p>
        <p class="app-stat-meta">Vie scolaire, reunions et activites.</p>
    </article>
</section>

<x-ui.card title="Filtrer le calendrier" subtitle="Consultez le mois en cours, changez de periode et ciblez un type d evenement si besoin.">
    <form method="GET" class="grid gap-3 lg:grid-cols-[220px_220px_auto_auto]">
        <input type="month" name="month" value="{{ $month->format('Y-m') }}" class="app-input">

        <select name="type" class="app-input">
            <option value="">Tous les types</option>
            @foreach($types as $eventType)
                <option value="{{ $eventType }}" @selected($type === $eventType)>
                    {{ \App\Models\SchoolCalendarEvent::labelForType($eventType) }}
                </option>
            @endforeach
        </select>

        <x-ui.button type="submit" variant="primary">Afficher</x-ui.button>
        <x-ui.button :href="url()->current()" variant="secondary">Reinitialiser</x-ui.button>
    </form>
</x-ui.card>

<section class="grid gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(320px,0.85fr)]">
    <x-ui.card title="Agenda du mois" subtitle="Lecture chronologique simple, sur mobile comme sur desktop.">
        <div class="space-y-4">
            @forelse($events as $event)
                @php
                    $typeLabel = \App\Models\SchoolCalendarEvent::labelForType((string) $event->type);
                    $variant = \App\Models\SchoolCalendarEvent::badgeVariant((string) $event->type);
                    $period = $event->ends_on && $event->ends_on->ne($event->starts_on)
                        ? $event->starts_on->format('d/m/Y').' au '.$event->ends_on->format('d/m/Y')
                        : $event->starts_on->format('d/m/Y');
                @endphp
                <article class="rounded-[26px] border border-slate-200 bg-white/95 p-5 shadow-sm">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <x-ui.badge :variant="$variant">{{ $typeLabel }}</x-ui.badge>
                                <span class="text-xs font-medium uppercase tracking-[0.16em] text-slate-400">
                                    {{ $period }}
                                </span>
                            </div>
                            <h3 class="mt-3 text-base font-semibold text-slate-950">{{ $event->title }}</h3>
                            @if(filled($event->description))
                                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $event->description }}</p>
                            @endif
                            @if($event->creator)
                                <p class="mt-3 text-xs text-slate-500">Ajoute par {{ $event->creator->name }}</p>
                            @endif
                        </div>

                        @if($showManageActions)
                            <div class="flex shrink-0 flex-wrap gap-2">
                                <x-ui.button :href="route('admin.calendar.edit', $event)" variant="secondary" class="px-3 py-2">
                                    Modifier
                                </x-ui.button>
                                <form method="POST" action="{{ route('admin.calendar.destroy', $event) }}" onsubmit="return confirm('Supprimer cet evenement du calendrier ?')">
                                    @csrf
                                    @method('DELETE')
                                    <x-ui.button type="submit" variant="danger" class="px-3 py-2">
                                        Supprimer
                                    </x-ui.button>
                                </form>
                            </div>
                        @endif
                    </div>
                </article>
            @empty
                <div class="student-empty">
                    Aucun evenement calendrier pour cette selection.
                </div>
            @endforelse
        </div>

        <div class="mt-5">
            {{ $events->links() }}
        </div>
    </x-ui.card>

    <x-ui.card title="A venir" subtitle="Les prochains rendez-vous visibles pour l ecole active.">
        <div class="space-y-3">
            @forelse($upcoming as $event)
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold text-slate-950">{{ $event->title }}</p>
                            <p class="mt-1 text-sm text-slate-600">
                                {{ \App\Models\SchoolCalendarEvent::labelForType((string) $event->type) }}
                            </p>
                        </div>
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">
                            {{ $event->starts_on->format('d/m') }}
                        </p>
                    </div>
                </div>
            @empty
                <div class="student-empty">Aucun evenement a venir pour le moment.</div>
            @endforelse
        </div>
    </x-ui.card>
</section>
