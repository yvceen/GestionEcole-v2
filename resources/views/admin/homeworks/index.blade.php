<x-admin-layout
    :title="$portalTitle ?? 'Devoirs'"
    subtitle="Suivez les demandes des enseignants, validez les publications et gardez une vue claire sur le flux des devoirs."
>
    @php
        $routePrefix = $routePrefix ?? 'admin.homeworks';
        $canCreate = $canCreate ?? true;
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

        $desktopStatusTone = static function (string $normalized): array {
            return match ($normalized) {
                'approved' => ['badge' => 'success', 'label' => 'Approuve', 'row' => 'hover:bg-emerald-50/40'],
                'rejected' => ['badge' => 'danger', 'label' => 'Rejete', 'row' => 'hover:bg-rose-50/40'],
                default => ['badge' => 'warning', 'label' => 'En attente', 'row' => 'hover:bg-amber-50/30'],
            };
        };
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
        <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
            <div class="max-w-2xl">
                <p class="app-overline">Validation</p>
                <h2 class="mt-2 text-xl font-semibold tracking-tight text-slate-950">Gestion des devoirs</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">
                    Recherchez rapidement une demande, filtrez les statuts et gardez un espace d action plus lisible pour l equipe de validation.
                </p>
            </div>

            @if($canCreate)
                <x-ui.button :href="route($routePrefix . '.create')" variant="primary" class="w-full sm:w-auto xl:shrink-0">
                    Nouveau devoir
                </x-ui.button>
            @endif
        </div>

        <form method="GET" class="mt-6 grid gap-4 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-end">
            <div class="grid gap-4 md:grid-cols-2 2xl:grid-cols-[minmax(0,1.5fr)_220px_220px_180px]">
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

            <div class="flex flex-col gap-3 sm:flex-row xl:justify-end">
                <x-ui.button type="submit" variant="primary" class="w-full sm:w-auto">
                    Filtrer
                </x-ui.button>
                <x-ui.button :href="route($routePrefix . '.index')" variant="secondary" class="w-full sm:w-auto">
                    Reinitialiser
                </x-ui.button>
            </div>
        </form>
    </section>

    <x-ui.card
        title="Demandes a valider"
        subtitle="Consultez rapidement chaque devoir, son contexte de publication et les actions disponibles."
        class="mt-5"
    >
        <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-wrap items-center gap-2">
                <x-ui.badge variant="info">{{ $homeworks->total() }} resultat(s)</x-ui.badge>
                @if(($statusFilter ?? 'all') !== 'all')
                    <x-ui.badge variant="warning">Filtre actif</x-ui.badge>
                @endif
            </div>
            <p class="text-sm text-slate-500">
                Vue type rendez-vous, plus lisible sur mobile comme sur desktop.
            </p>
        </div>

        @if($homeworks->isEmpty())
            <div class="rounded-[28px] border border-dashed border-slate-300 bg-slate-50/80 px-6 py-14 text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-white shadow-sm ring-1 ring-slate-200">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-6 w-6 text-slate-400" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 6h8M8 10h8M8 14h5M6 4h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z"/>
                    </svg>
                </div>
                <h3 class="mt-4 text-base font-semibold text-slate-900">Aucun devoir trouve</h3>
                <p class="mt-2 text-sm leading-6 text-slate-500">
                    Aucun devoir ne correspond aux filtres actuels. Ajustez la recherche ou reinitialisez les criteres pour afficher plus de demandes.
                </p>
                <div class="mt-6 flex justify-center">
                    <x-ui.button :href="route($routePrefix . '.index')" variant="secondary">Reinitialiser les filtres</x-ui.button>
                </div>
            </div>
        @else
            <div class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
                @foreach($homeworks as $hw)
                    @php
                        $normalized = $hw->normalized_status ?? 'pending';
                        $statusMeta = $desktopStatusTone($normalized);
                        $summary = trim((string) ($hw->description ?? ''));
                    @endphp
                    <article class="group flex h-full flex-col rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-[0_18px_40px_-26px_rgba(15,23,42,0.28)]">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-[0.68rem] font-semibold uppercase tracking-[0.16em] text-slate-500">Demande de devoir</p>
                                <h3 class="mt-2 line-clamp-2 text-lg font-semibold tracking-tight text-slate-950">{{ $hw->title }}</h3>
                            </div>
                            <x-ui.badge :variant="$statusMeta['badge']" class="shrink-0 uppercase tracking-[0.12em]">
                                {{ $statusMeta['label'] }}
                            </x-ui.badge>
                        </div>

                        <p class="mt-3 line-clamp-3 text-sm leading-6 text-slate-500">
                            {{ $summary !== '' ? \Illuminate\Support\Str::limit($summary, 150) : 'Aucun resume ajoute pour cette demande.' }}
                        </p>

                        <div class="mt-5 grid gap-3 sm:grid-cols-2">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3">
                                <p class="text-[0.68rem] font-semibold uppercase tracking-[0.16em] text-slate-500">Classe</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900">{{ $hw->classroom?->name ?? '-' }}</p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3">
                                <p class="text-[0.68rem] font-semibold uppercase tracking-[0.16em] text-slate-500">Enseignant</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900">{{ $hw->teacher?->name ?? '-' }}</p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3">
                                <p class="text-[0.68rem] font-semibold uppercase tracking-[0.16em] text-slate-500">Echeance</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900">{{ optional($hw->due_at)->format('d/m/Y') ?? '-' }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ optional($hw->due_at)->format('H:i') ?? 'Sans heure' }}</p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3">
                                <p class="text-[0.68rem] font-semibold uppercase tracking-[0.16em] text-slate-500">Pieces jointes</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900">{{ (int) ($hw->attachments_count ?? 0) }}</p>
                                <p class="mt-1 text-xs text-slate-500">Documents ajoutes a la demande</p>
                            </div>
                        </div>

                        <div class="mt-4 rounded-2xl border border-slate-200 bg-white/70 px-4 py-3">
                            <p class="text-[0.68rem] font-semibold uppercase tracking-[0.16em] text-slate-500">Cree le</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">{{ optional($hw->created_at)->format('d/m/Y') ?? '-' }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ optional($hw->created_at)->format('H:i') ?? 'Heure non renseignee' }}</p>
                        </div>

                        <div class="mt-5 border-t border-slate-200 pt-4">
                            <div class="flex flex-wrap gap-2">
                                <x-ui.button :href="route($routePrefix . '.show', $hw)" variant="ghost" size="sm">
                                    Voir
                                </x-ui.button>
                                <x-ui.button :href="route($routePrefix . '.edit', $hw)" variant="secondary" size="sm">
                                    Modifier
                                </x-ui.button>

                                @if($normalized === 'pending')
                                    <form method="POST" action="{{ route($routePrefix . '.approve', $hw) }}">
                                        @csrf
                                        <x-ui.button type="submit" variant="outline" size="sm">
                                            Approuver
                                        </x-ui.button>
                                    </form>
                                    <form method="POST" action="{{ route($routePrefix . '.reject', $hw) }}">
                                        @csrf
                                        <x-ui.button type="submit" variant="danger" size="sm">
                                            Rejeter
                                        </x-ui.button>
                                    </form>
                                @endif

                                <form method="POST" action="{{ route($routePrefix . '.destroy', $hw) }}">
                                    @csrf
                                    @method('DELETE')
                                    <x-ui.button type="submit" variant="danger" size="sm" onclick="return confirm('Supprimer ce devoir ?');">
                                        Supprimer
                                    </x-ui.button>
                                </form>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </x-ui.card>

    <div class="mt-6">{{ $homeworks->links() }}</div>
</x-admin-layout>
