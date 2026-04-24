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
                <p class="mt-1 text-sm text-slate-500">Acces vie scolaire pour relire, approuver, corriger ou retirer un devoir.</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <x-ui.badge variant="info">{{ $homeworks->total() }} resultat(s)</x-ui.badge>
                @if(($statusFilter ?? 'all') !== 'all')
                    <x-ui.badge variant="warning">Filtre actif</x-ui.badge>
                @endif
            </div>
        </div>

        <div class="hidden overflow-x-auto md:block">
            <table class="app-table min-w-[980px]">
                <thead>
                    <tr>
                        <th class="min-w-[130px]">Classe</th>
                        <th class="min-w-[250px]">Titre</th>
                        <th class="min-w-[170px]">Enseignant</th>
                        <th class="min-w-[165px]">Echeance</th>
                        <th class="min-w-[120px]">Pieces jointes</th>
                        <th class="min-w-[120px]">Statut</th>
                        <th class="min-w-[165px]">Cree le</th>
                        <th class="min-w-[260px] text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
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
                        <tr>
                            <td><div class="font-semibold text-slate-900">{{ $hw->classroom?->name ?? '-' }}</div></td>
                            <td>
                                <div class="space-y-1">
                                    <p class="font-semibold text-slate-950">{{ $hw->title }}</p>
                                    <p class="text-xs text-slate-500">Maintenance et validation depuis la vie scolaire.</p>
                                </div>
                            </td>
                            <td>
                                <div class="space-y-1">
                                    <p class="font-medium text-slate-800">{{ $hw->teacher?->name ?? '-' }}</p>
                                    <p class="text-xs text-slate-500">Soumission enseignant</p>
                                </div>
                            </td>
                            <td class="text-slate-600">{{ optional($hw->due_at)->format('Y-m-d H:i') ?? '-' }}</td>
                            <td>
                                <span class="inline-flex min-w-10 items-center justify-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-700">
                                    {{ (int) ($hw->attachments_count ?? 0) }}
                                </span>
                            </td>
                            <td>
                                <x-ui.badge :variant="$badgeVariant" class="uppercase tracking-[0.12em]">
                                    {{ $statusLabel }}
                                </x-ui.badge>
                            </td>
                            <td class="text-slate-500">{{ optional($hw->created_at)->format('Y-m-d H:i') }}</td>
                            <td>
                                <div class="flex flex-wrap justify-end gap-2">
                                    <x-ui.button :href="route($routePrefix . '.show', $hw)" variant="secondary" size="sm">Voir</x-ui.button>
                                    <x-ui.button :href="route($routePrefix . '.edit', $hw)" variant="ghost" size="sm">Modifier</x-ui.button>

                                    @if($normalized === 'pending')
                                        <form method="POST" action="{{ route($routePrefix . '.approve', $hw) }}">
                                            @csrf
                                            <button class="app-button-outline min-h-9 rounded-lg px-3 py-2 text-xs">Approuver</button>
                                        </form>
                                        <form method="POST" action="{{ route($routePrefix . '.reject', $hw) }}">
                                            @csrf
                                            <button class="app-button-danger min-h-9 rounded-lg px-3 py-2 text-xs">Rejeter</button>
                                        </form>
                                    @endif

                                    <form method="POST" action="{{ route($routePrefix . '.destroy', $hw) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="app-button-danger min-h-9 rounded-lg px-3 py-2 text-xs" onclick="return confirm('Supprimer ce devoir ?');">Supprimer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-14 text-center text-sm text-slate-500">Aucun devoir pour ce filtre.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="mt-6">{{ $homeworks->links() }}</div>
</x-school-life-layout>
