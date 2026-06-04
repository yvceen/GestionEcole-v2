<x-school-life-layout title="Demandes de recuperation" subtitle="Gerez les sorties, verifiez la personne autorisee et consultez l'historique quotidien.">
    @php
        $statusLabels = [
            'pending' => 'En attente',
            'approved' => 'Approuvee',
            'rejected' => 'Rejetee',
            'completed' => 'Sortie confirmee',
        ];
    @endphp

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="app-stat-card border-amber-100 bg-amber-50/70">
            <p class="app-stat-label">En attente</p>
            <p class="app-stat-value text-amber-700">{{ $stats['pending'] }}</p>
            <p class="app-stat-meta">Demandes du {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }} a traiter.</p>
        </article>
        <article class="app-stat-card border-emerald-100 bg-emerald-50/70">
            <p class="app-stat-label">Approuvees</p>
            <p class="app-stat-value text-emerald-700">{{ $stats['approved'] }}</p>
            <p class="app-stat-meta">En attente de sortie effective.</p>
        </article>
        <article class="app-stat-card border-sky-100 bg-sky-50/70">
            <p class="app-stat-label">Sorties confirmees</p>
            <p class="app-stat-value text-sky-700">{{ $stats['completed'] }}</p>
            <p class="app-stat-meta">Eleves deja sortis ce jour.</p>
        </article>
        <article class="app-stat-card border-rose-100 bg-rose-50/70">
            <p class="app-stat-label">Rejetees</p>
            <p class="app-stat-value text-rose-700">{{ $stats['rejected'] }}</p>
            <p class="app-stat-meta">Demandes refusees ce jour.</p>
        </article>
    </section>

    <x-ui.card title="Journee a consulter" subtitle="Choisissez une date et, si necessaire, un statut precis.">
        <form method="GET" class="grid gap-3 md:grid-cols-[minmax(190px,0.8fr)_minmax(220px,1fr)_auto_auto] md:items-end">
            <x-ui.input label="Date" type="date" name="date" :value="$date" />
            <x-ui.select label="Statut" name="status">
                <option value="">Tous les statuts</option>
                @foreach(\App\Models\PickupRequest::statuses() as $requestStatus)
                    <option value="{{ $requestStatus }}" @selected($status === $requestStatus)>
                        {{ $statusLabels[$requestStatus] }}
                    </option>
                @endforeach
            </x-ui.select>
            <x-ui.button type="submit" variant="primary">Afficher</x-ui.button>
            <x-ui.button :href="route('school-life.pickup-requests.index')" variant="secondary">Aujourd'hui</x-ui.button>
        </form>
    </x-ui.card>

    <section class="app-card overflow-hidden">
        <header class="flex flex-col gap-3 border-b border-slate-200 bg-slate-50/70 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-950">Demandes de la journee</h2>
                <p class="mt-1 text-sm text-slate-500">Classees par heure de recuperation demandee.</p>
            </div>
            <x-ui.badge variant="info">{{ $requests->total() }} demande(s)</x-ui.badge>
        </header>

        <div class="divide-y divide-slate-200/80">
            @forelse($requests as $pickup)
                @php
                    $variant = match ($pickup->status) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'completed' => 'info',
                        default => 'warning',
                    };
                @endphp
                <article class="grid gap-4 px-5 py-5 transition hover:bg-sky-50/40 xl:grid-cols-[100px_minmax(170px,1fr)_minmax(220px,1.15fr)_minmax(220px,1fr)_minmax(280px,auto)] xl:items-center">
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">Heure prevue</p>
                        <p class="mt-1 text-xl font-semibold text-slate-950">{{ $pickup->requested_pickup_at?->format('H:i') ?? '-' }}</p>
                        <x-ui.badge :variant="$variant" class="mt-2 whitespace-nowrap">{{ $statusLabels[$pickup->status] ?? $pickup->status }}</x-ui.badge>
                    </div>

                    <div class="min-w-0">
                        <p class="truncate text-base font-semibold text-slate-950">{{ $pickup->student?->full_name ?? '-' }}</p>
                        <p class="mt-1 truncate text-sm text-slate-500">{{ $pickup->student?->classroom?->name ?? '-' }}</p>
                        @if($pickup->reason)
                            <p class="mt-2 line-clamp-2 text-xs leading-5 text-slate-600">{{ $pickup->reason }}</p>
                        @endif
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-3">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Personne autorisee</p>
                        <p class="mt-1 truncate text-sm font-semibold text-slate-900">{{ $pickup->pickup_person_name ?: ($pickup->parentUser?->name ?? '-') }}</p>
                        <p class="mt-1 truncate text-xs text-slate-500">
                            {{ $pickup->pickup_person_relationship ?: 'Lien non precise' }}
                            @if($pickup->pickup_person_phone)
                                | {{ $pickup->pickup_person_phone }}
                            @endif
                        </p>
                        <p class="mt-2 text-xs text-slate-500">Parent demandeur: {{ $pickup->parentUser?->name ?? '-' }}</p>
                    </div>

                    <div class="rounded-xl border border-sky-100 bg-sky-50/70 px-3 py-3">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-sky-700">Code de verification</p>
                        <p class="mt-1 font-mono text-lg font-bold tracking-[0.18em] text-sky-800">{{ $pickup->verification_code ?: '-' }}</p>
                        @if($pickup->reviewedBy)
                            <p class="mt-2 text-xs text-slate-500">Suivi par {{ $pickup->reviewedBy->name }}</p>
                        @endif
                        @if($pickup->decision_note)
                            <p class="mt-1 line-clamp-2 text-xs text-slate-600">{{ $pickup->decision_note }}</p>
                        @endif
                    </div>

                    <div class="flex flex-wrap gap-2 xl:justify-end">
                        @if($pickup->status === 'pending')
                            <form method="POST" action="{{ route('school-life.pickup-requests.approve', $pickup) }}">
                                @csrf
                                <button class="app-button-outline min-h-9 whitespace-nowrap rounded-lg px-3 py-2 text-xs">Approuver</button>
                            </form>
                            <form method="POST" action="{{ route('school-life.pickup-requests.reject', $pickup) }}">
                                @csrf
                                <button class="app-button-danger min-h-9 whitespace-nowrap rounded-lg px-3 py-2 text-xs">Rejeter</button>
                            </form>
                        @elseif($pickup->status === 'approved')
                            <form method="POST" action="{{ route('school-life.pickup-requests.complete', $pickup) }}" class="flex min-w-0 flex-1 gap-2 xl:max-w-[320px]">
                                @csrf
                                <input name="decision_note" class="app-input min-h-9 py-1.5 text-xs" placeholder="Note de sortie optionnelle">
                                <button class="app-button-primary min-h-9 whitespace-nowrap rounded-lg px-3 py-2 text-xs">Confirmer la sortie</button>
                            </form>
                        @elseif($pickup->status === 'completed')
                            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700">
                                Sorti a {{ $pickup->completed_at?->format('H:i') ?? '-' }}
                            </div>
                        @else
                            <div class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700">Demande rejetee</div>
                        @endif
                    </div>
                </article>
            @empty
                <div class="px-6 py-14 text-center">
                    <p class="text-sm font-semibold text-slate-800">Aucune demande pour cette journee.</p>
                    <p class="mt-1 text-xs text-slate-500">Les nouvelles demandes des parents apparaitront ici.</p>
                </div>
            @endforelse
        </div>
    </section>

    <section class="app-card mt-6 overflow-hidden">
        <header class="flex flex-col gap-3 border-b border-slate-200 bg-[linear-gradient(135deg,rgba(16,185,129,0.08),rgba(248,250,252,0.95))] px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-950">Sorties du {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</h2>
                <p class="mt-1 text-sm text-slate-500">Chaque eleve est classe selon l'heure reelle de sortie.</p>
            </div>
            <x-ui.badge variant="success">{{ $dailyExits->count() }} sortie(s)</x-ui.badge>
        </header>

        <div class="divide-y divide-slate-200/80">
            @forelse($dailyExits as $index => $exit)
                <article class="grid gap-3 px-5 py-4 sm:grid-cols-[52px_90px_minmax(180px,1fr)_minmax(180px,1fr)_minmax(150px,0.8fr)] sm:items-center">
                    <div class="grid h-9 w-9 place-items-center rounded-full bg-emerald-50 text-sm font-bold text-emerald-700 ring-1 ring-emerald-100">{{ $index + 1 }}</div>
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Sortie</p>
                        <p class="mt-1 text-lg font-semibold text-slate-950">{{ $exit->completed_at?->format('H:i') ?? '-' }}</p>
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-slate-950">{{ $exit->student?->full_name ?? '-' }}</p>
                        <p class="mt-1 truncate text-xs text-slate-500">{{ $exit->student?->classroom?->name ?? '-' }}</p>
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-slate-800">{{ $exit->pickup_person_name ?: ($exit->parentUser?->name ?? '-') }}</p>
                        <p class="mt-1 truncate text-xs text-slate-500">{{ $exit->pickup_person_relationship ?: 'Lien non precise' }}</p>
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-xs font-medium text-slate-600">{{ $exit->reviewedBy?->name ?? 'Responsable non precise' }}</p>
                        @if($exit->decision_note)
                            <p class="mt-1 truncate text-xs text-slate-500">{{ $exit->decision_note }}</p>
                        @endif
                    </div>
                </article>
            @empty
                <div class="px-6 py-12 text-center text-sm text-slate-500">Aucune sortie confirmee pour cette date.</div>
            @endforelse
        </div>
    </section>

    <div class="mt-5">{{ $requests->links() }}</div>
</x-school-life-layout>
