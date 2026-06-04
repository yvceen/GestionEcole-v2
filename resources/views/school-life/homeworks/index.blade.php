<x-school-life-layout title="Gestion des devoirs" subtitle="Consultez, validez et corrigez les devoirs visibles pour la vie scolaire.">
    @php
        $routePrefix = $routePrefix ?? 'school-life.homeworks';
        $summaryCards = [
            [
                'label' => 'En attente',
                'value' => $stats['pending'] ?? 0,
                'accent' => 'border-amber-200 bg-amber-50/80 text-amber-700',
                'ring' => 'bg-amber-100',
                'note' => 'Demandes a traiter en priorite.',
            ],
            [
                'label' => 'Approuves',
                'value' => $stats['approved'] ?? 0,
                'accent' => 'border-emerald-200 bg-emerald-50/80 text-emerald-700',
                'ring' => 'bg-emerald-100',
                'note' => 'Publies apres validation.',
            ],
            [
                'label' => 'Rejetes',
                'value' => $stats['rejected'] ?? 0,
                'accent' => 'border-rose-200 bg-rose-50/80 text-rose-700',
                'ring' => 'bg-rose-100',
                'note' => 'A revoir ou corriger avant republication.',
            ],
            [
                'label' => 'Cette semaine',
                'value' => $stats['this_week'] ?? 0,
                'accent' => 'border-slate-200 bg-slate-50/90 text-slate-700',
                'ring' => 'bg-slate-200',
                'note' => 'Demandes recues sur les 7 derniers jours.',
            ],
        ];
    @endphp

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach($summaryCards as $card)
            <article class="app-card flex min-h-[10.25rem] flex-col justify-between px-5 py-5">
                <div class="flex items-center justify-between gap-3">
                    <span class="inline-flex items-center rounded-full border px-3 py-1 text-[0.68rem] font-semibold uppercase tracking-[0.16em] {{ $card['accent'] }}">
                        {{ $card['label'] }}
                    </span>
                    <span class="h-2.5 w-2.5 rounded-full {{ $card['ring'] }}" aria-hidden="true"></span>
                </div>

                <div class="mt-6">
                    <p class="text-[0.72rem] font-semibold uppercase tracking-[0.16em] text-slate-500">Volume</p>
                    <p class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">{{ $card['value'] }}</p>
                    <p class="mt-2 text-sm leading-6 text-slate-500">{{ $card['note'] }}</p>
                </div>
            </article>
        @endforeach
    </section>

    <section class="app-card px-5 py-5 md:px-6">
        <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
            <div class="max-w-2xl">
                <p class="app-overline">Moderation</p>
                <h2 class="mt-2 text-xl font-semibold tracking-tight text-slate-950">Suivi des devoirs</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">
                    Recherchez rapidement une demande, filtrez les statuts et gardez une vue claire sur les devoirs a valider ou corriger.
                </p>
            </div>
        </div>

        <form method="GET" class="mt-6 flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
            <div class="grid flex-1 gap-4 md:grid-cols-2 xl:grid-cols-[minmax(0,1.5fr)_220px_220px_180px]">
                <x-ui.input
                    label="Recherche"
                    name="q"
                    :value="$q"
                    placeholder="Titre, classe ou enseignant"
                />

                <x-ui.select label="Statut" name="status">
                    <option value="all" @selected(($statusFilter ?? 'all') === 'all')>Tous les statuts</option>
                    <option value="pending" @selected(($statusFilter ?? 'all') === 'pending')>En attente</option>
                    <option value="approved" @selected(($statusFilter ?? 'all') === 'approved')>Approuves</option>
                    <option value="rejected" @selected(($statusFilter ?? 'all') === 'rejected')>Rejetes</option>
                </x-ui.select>

                <x-ui.select label="Classe" name="classroom_id">
                    <option value="">Toutes les classes</option>
                    @foreach(($classrooms ?? collect()) as $classroom)
                        <option value="{{ $classroom->id }}" @selected(((int) ($classroomId ?? 0)) === (int) $classroom->id)>
                            {{ $classroom->name }}
                        </option>
                    @endforeach
                </x-ui.select>

                <x-ui.input
                    label="Date"
                    type="date"
                    name="date"
                    :value="$dateFilter ?? ''"
                />
            </div>

            <div class="flex flex-col gap-3 sm:flex-row xl:shrink-0">
                <x-ui.button type="submit" variant="primary" class="w-full sm:w-auto">
                    Filtrer
                </x-ui.button>
                <x-ui.button :href="route($routePrefix . '.index')" variant="secondary" class="w-full sm:w-auto">
                    Reinitialiser
                </x-ui.button>
            </div>
        </form>
    </section>

    <section class="app-card mt-5 overflow-hidden p-0">
        <div class="flex flex-col gap-4 border-b border-slate-200/80 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-base font-semibold text-slate-950">Devoirs a suivre</h3>
                <p class="mt-1 text-sm text-slate-500">Accès vie scolaire pour relire, approuver, corriger ou retirer un devoir.</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <x-ui.badge variant="info">{{ $homeworks->total() }} resultat(s)</x-ui.badge>
                @if(($statusFilter ?? 'all') !== 'all')
                    <x-ui.badge variant="warning">Filtre actif</x-ui.badge>
                @endif
            </div>
        </div>

        <div class="divide-y divide-slate-200/80">
            @forelse($homeworks as $hw)
                @php
                    $normalized = $hw->normalized_status ?? 'pending';
                    $badgeVariant = match($normalized) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'warning',
                    };
                    $statusLabel = match($normalized) {
                        'approved' => 'Approuve',
                        'rejected' => 'Rejete',
                        default => 'En attente',
                    };
                @endphp

                <article class="grid gap-4 px-5 py-4 transition hover:bg-sky-50/50 xl:grid-cols-[minmax(120px,0.7fr)_minmax(180px,1.35fr)_minmax(150px,1fr)_minmax(130px,0.8fr)_auto_minmax(320px,auto)] xl:items-center">
                    <div class="min-w-0">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400 xl:hidden">Classe</p>
                        <p class="truncate text-sm font-semibold text-slate-900">{{ $hw->classroom?->name ?? '-' }}</p>
                    </div>

                    <div class="min-w-0">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400 xl:hidden">Devoir</p>
                        <a href="{{ route($routePrefix . '.show', $hw) }}" class="block truncate text-sm font-semibold text-slate-950 transition hover:text-sky-700">
                            {{ $hw->title }}
                        </a>
                        <p class="mt-0.5 truncate text-xs text-slate-500">
                            {{ (int) ($hw->attachments_count ?? 0) }} piece(s) jointe(s)
                        </p>
                    </div>

                    <div class="min-w-0">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400 xl:hidden">Enseignant</p>
                        <p class="truncate text-sm font-medium text-slate-800">{{ $hw->teacher?->name ?? '-' }}</p>
                    </div>

                    <div class="min-w-0">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400 xl:hidden">Echeance</p>
                        <p class="whitespace-nowrap text-xs font-medium text-slate-600">{{ optional($hw->due_at)->format('d/m/Y H:i') ?? '-' }}</p>
                    </div>

                    <div>
                        <x-ui.badge :variant="$badgeVariant" class="whitespace-nowrap uppercase tracking-[0.08em]">
                            {{ $statusLabel }}
                        </x-ui.badge>
                    </div>

                    <div class="flex flex-wrap items-center gap-1.5 xl:flex-nowrap xl:justify-end">
                        <a href="{{ route($routePrefix . '.show', $hw) }}" class="app-button-secondary min-h-8 whitespace-nowrap rounded-lg px-2.5 py-1.5 text-[11px]">Voir</a>
                        <a href="{{ route($routePrefix . '.edit', $hw) }}" class="app-button-ghost min-h-8 whitespace-nowrap rounded-lg px-2.5 py-1.5 text-[11px]">Modifier</a>

                        @if($normalized === 'pending')
                            <form method="POST" action="{{ route($routePrefix . '.approve', $hw) }}">
                                @csrf
                                <button class="app-button-outline min-h-8 whitespace-nowrap rounded-lg px-2.5 py-1.5 text-[11px]">Approuver</button>
                            </form>
                            <form method="POST" action="{{ route($routePrefix . '.reject', $hw) }}">
                                @csrf
                                <button class="app-button-danger min-h-8 whitespace-nowrap rounded-lg px-2.5 py-1.5 text-[11px]">Rejeter</button>
                            </form>
                        @endif

                        <form method="POST" action="{{ route($routePrefix . '.destroy', $hw) }}">
                            @csrf
                            @method('DELETE')
                            <button class="app-button-danger min-h-8 whitespace-nowrap rounded-lg px-2.5 py-1.5 text-[11px]" onclick="return confirm('Supprimer ce devoir ?');">Supprimer</button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="px-6 py-14 text-center">
                    <p class="text-sm font-semibold text-slate-800">Aucun devoir pour ce filtre.</p>
                    <p class="mt-1 text-xs text-slate-500">Les devoirs soumis par les enseignants apparaitront ici pour validation.</p>
                </div>
            @endforelse
        </div>
    </section>

    <div class="mt-6">{{ $homeworks->links() }}</div>
</x-school-life-layout>
