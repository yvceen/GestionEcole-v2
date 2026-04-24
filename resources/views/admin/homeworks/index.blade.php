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

    <section class="app-card mt-5 overflow-hidden p-0">
        <div class="flex flex-col gap-4 border-b border-slate-200/80 px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <h3 class="text-base font-semibold text-slate-950">Demandes a valider</h3>
                <p class="mt-1 text-sm text-slate-500">Lecture plus claire des devoirs, pieces jointes, statuts et actions de moderation.</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <x-ui.badge variant="info">{{ $homeworks->total() }} resultat(s)</x-ui.badge>
                @if(($statusFilter ?? 'all') !== 'all')
                    <x-ui.badge variant="warning">Filtre actif</x-ui.badge>
                @endif
            </div>
        </div>

        <div class="grid gap-4 px-4 py-4 xl:hidden md:grid-cols-2">
            @forelse($homeworks as $hw)
                @php
                    $normalized = $hw->normalized_status ?? 'pending';
                    $statusMeta = $desktopStatusTone($normalized);
                @endphp
                <article class="rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm transition hover:border-slate-300 hover:shadow-md">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="line-clamp-2 text-base font-semibold text-slate-950">{{ $hw->title }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $hw->classroom?->name ?? '-' }} | {{ $hw->teacher?->name ?? '-' }}</p>
                        </div>
                        <x-ui.badge :variant="$statusMeta['badge']">{{ $statusMeta['label'] }}</x-ui.badge>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-3">
                            <p class="text-[0.68rem] font-semibold uppercase tracking-[0.16em] text-slate-500">Echeance</p>
                            <p class="mt-1 font-semibold text-slate-900">{{ optional($hw->due_at)->format('d/m/Y H:i') ?? '-' }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-3">
                            <p class="text-[0.68rem] font-semibold uppercase tracking-[0.16em] text-slate-500">Pieces</p>
                            <p class="mt-1 font-semibold text-slate-900">{{ (int) ($hw->attachments_count ?? 0) }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-3 col-span-2">
                            <p class="text-[0.68rem] font-semibold uppercase tracking-[0.16em] text-slate-500">Cree le</p>
                            <p class="mt-1 font-semibold text-slate-900">{{ optional($hw->created_at)->format('d/m/Y H:i') ?? '-' }}</p>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <x-ui.button :href="route($routePrefix . '.show', $hw)" variant="secondary" size="sm">
                            Voir
                        </x-ui.button>
                        <x-ui.button :href="route($routePrefix . '.edit', $hw)" variant="ghost" size="sm">
                            Modifier
                        </x-ui.button>

                        @if($normalized === 'pending')
                            <form method="POST" action="{{ route($routePrefix . '.approve', $hw) }}">
                                @csrf
                                <button class="app-button-outline min-h-9 rounded-lg px-3 py-2 text-xs">
                                    Approuver
                                </button>
                            </form>
                            <form method="POST" action="{{ route($routePrefix . '.reject', $hw) }}">
                                @csrf
                                <button class="app-button-danger min-h-9 rounded-lg px-3 py-2 text-xs">
                                    Rejeter
                                </button>
                            </form>
                        @endif

                        <form method="POST" action="{{ route($routePrefix . '.destroy', $hw) }}">
                            @csrf
                            @method('DELETE')
                            <button class="app-button-danger min-h-9 rounded-lg px-3 py-2 text-xs" onclick="return confirm('Supprimer ce devoir ?');">
                                Supprimer
                            </button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="col-span-full px-4 py-10 text-center text-sm text-slate-500">
                    Aucun devoir pour ce filtre.
                </div>
            @endforelse
        </div>

        <div class="hidden xl:block">
            <div class="overflow-x-auto">
                <table class="w-full table-fixed border-separate border-spacing-0">
                    <colgroup>
                        <col class="w-[14%]">
                        <col class="w-[28%]">
                        <col class="w-[15%]">
                        <col class="w-[13%]">
                        <col class="w-[9%]">
                        <col class="w-[9%]">
                        <col class="w-[12%]">
                    </colgroup>
                    <thead class="bg-slate-50/95">
                        <tr class="text-left text-[0.72rem] font-semibold uppercase tracking-[0.16em] text-slate-500">
                            <th class="px-5 py-4">Titre</th>
                            <th class="px-5 py-4">Classe / enseignant</th>
                            <th class="px-5 py-4">Echeance</th>
                            <th class="px-5 py-4">Pieces</th>
                            <th class="px-5 py-4">Statut</th>
                            <th class="px-5 py-4">Cree le</th>
                            <th class="px-5 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="align-top">
                        @forelse($homeworks as $hw)
                            @php
                                $normalized = $hw->normalized_status ?? 'pending';
                                $statusMeta = $desktopStatusTone($normalized);
                            @endphp
                            <tr class="border-t border-slate-200/80 transition {{ $statusMeta['row'] }}">
                                <td class="px-5 py-4">
                                    <div class="min-w-0">
                                        <p class="line-clamp-2 font-semibold text-slate-950">{{ $hw->title }}</p>
                                        <p class="mt-1 line-clamp-2 text-xs leading-5 text-slate-500">
                                            {{ $hw->subject?->name ? $hw->subject->name . ' - ' : '' }}Validation et maintenance apres publication depuis le meme flux.
                                        </p>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="space-y-1">
                                        <p class="font-semibold text-slate-900">{{ $hw->classroom?->name ?? '-' }}</p>
                                        <p class="text-sm text-slate-600">{{ $hw->teacher?->name ?? '-' }}</p>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">
                                    {{ optional($hw->due_at)->format('d/m/Y') ?? '-' }}
                                    <div class="mt-1 text-xs text-slate-500">
                                        {{ optional($hw->due_at)->format('H:i') ?? 'Sans heure' }}
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex min-w-10 items-center justify-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-700">
                                        {{ (int) ($hw->attachments_count ?? 0) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <x-ui.badge :variant="$statusMeta['badge']" class="uppercase tracking-[0.12em]">
                                        {{ $statusMeta['label'] }}
                                    </x-ui.badge>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-500">
                                    {{ optional($hw->created_at)->format('d/m/Y') ?? '-' }}
                                    <div class="mt-1 text-xs text-slate-400">{{ optional($hw->created_at)->format('H:i') ?? '' }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end">
                                        <details class="relative">
                                            <summary class="list-none cursor-pointer rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                                                Actions
                                            </summary>
                                            <div class="absolute right-0 z-20 mt-2 w-48 rounded-2xl border border-slate-200 bg-white p-2 shadow-[0_18px_48px_-24px_rgba(15,23,42,0.3)]">
                                                <div class="flex flex-col gap-2">
                                                    <x-ui.button :href="route($routePrefix . '.show', $hw)" variant="secondary" size="sm" class="justify-start">
                                                        Voir
                                                    </x-ui.button>
                                                    <x-ui.button :href="route($routePrefix . '.edit', $hw)" variant="ghost" size="sm" class="justify-start">
                                                        Modifier
                                                    </x-ui.button>

                                                    @if($normalized === 'pending')
                                                        <form method="POST" action="{{ route($routePrefix . '.approve', $hw) }}">
                                                            @csrf
                                                            <button class="app-button-outline min-h-9 w-full rounded-lg px-3 py-2 text-left text-xs">
                                                                Approuver
                                                            </button>
                                                        </form>
                                                        <form method="POST" action="{{ route($routePrefix . '.reject', $hw) }}">
                                                            @csrf
                                                            <button class="app-button-danger min-h-9 w-full rounded-lg px-3 py-2 text-left text-xs">
                                                                Rejeter
                                                            </button>
                                                        </form>
                                                    @endif

                                                    <form method="POST" action="{{ route($routePrefix . '.destroy', $hw) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="app-button-danger min-h-9 w-full rounded-lg px-3 py-2 text-left text-xs" onclick="return confirm('Supprimer ce devoir ?');">
                                                            Supprimer
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </details>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-14 text-center text-sm text-slate-500">
                                    Aucun devoir pour ce filtre.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <div class="mt-6">{{ $homeworks->links() }}</div>
</x-admin-layout>
