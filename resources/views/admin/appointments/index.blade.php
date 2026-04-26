@php
    $layoutComponent = $layoutComponent ?? 'admin-layout';
    $routePrefix = $routePrefix ?? 'admin.appointments';
    $canCreate = $canCreate ?? true;
    $canEdit = $canEdit ?? true;
    $canDelete = $canDelete ?? true;
    $canApprove = $canApprove ?? true;
@endphp

<x-dynamic-component :component="$layoutComponent" title="Rendez-vous">
    <x-ui.page-header
        title="Rendez-vous parents"
        subtitle="Filtrez les demandes, ouvrez le detail, puis traitez approbation, refus ou cloture depuis le module existant."
    >
        @if($canCreate)
            <x-slot name="actions">
                <x-ui.button :href="route($routePrefix . '.create')" variant="primary">Nouveau rendez-vous</x-ui.button>
            </x-slot>
        @endif
    </x-ui.page-header>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <div class="app-stat-card">
            <p class="app-stat-label">En attente</p>
            <p class="app-stat-value">{{ $stats['pending'] ?? 0 }}</p>
            <p class="app-stat-meta">Demandes a traiter.</p>
        </div>
        <div class="app-stat-card">
            <p class="app-stat-label">Approuves</p>
            <p class="app-stat-value">{{ $stats['approved'] ?? 0 }}</p>
            <p class="app-stat-meta">Valides par l administration.</p>
        </div>
        <div class="app-stat-card">
            <p class="app-stat-label">Termines</p>
            <p class="app-stat-value">{{ $stats['completed'] ?? 0 }}</p>
            <p class="app-stat-meta">Rendez-vous clotures.</p>
        </div>
        <div class="app-stat-card">
            <p class="app-stat-label">Refuses</p>
            <p class="app-stat-value">{{ $stats['rejected'] ?? 0 }}</p>
            <p class="app-stat-meta">Demandes non retenues.</p>
        </div>
        <div class="app-stat-card">
            <p class="app-stat-label">Cette semaine</p>
            <p class="app-stat-value">{{ $stats['this_week'] ?? 0 }}</p>
            <p class="app-stat-meta">Volume recu recemment.</p>
        </div>
    </section>

    <x-ui.card title="Filtres" subtitle="Recherchez par parent, enfant, message ou titre.">
        <form method="GET" class="grid gap-4 md:grid-cols-[minmax(0,1fr)_220px_auto]">
            <div class="app-field">
                <label for="appointments-q" class="app-label">Recherche</label>
                <input id="appointments-q" name="q" value="{{ $q ?? '' }}"
                       placeholder="Parent, enfant, telephone, titre..."
                       class="app-input">
            </div>

            <div class="app-field">
                <label for="appointments-status" class="app-label">Statut</label>
                <select id="appointments-status" name="status" class="app-input">
                    <option value="all" @selected(($statusFilter ?? 'all') === 'all')>Tous</option>
                    <option value="pending" @selected(($statusFilter ?? 'all') === 'pending')>En attente</option>
                    <option value="approved" @selected(($statusFilter ?? 'all') === 'approved')>Approuves</option>
                    <option value="completed" @selected(($statusFilter ?? 'all') === 'completed')>Termines</option>
                    <option value="rejected" @selected(($statusFilter ?? 'all') === 'rejected')>Refuses</option>
                </select>
            </div>

            <div class="flex items-end gap-3">
                <x-ui.button type="submit" variant="primary">Filtrer</x-ui.button>
                <x-ui.button :href="route($routePrefix . '.index')" variant="ghost">Reinitialiser</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    <x-ui.card title="Liste des rendez-vous" subtitle="Consultez les informations parent, l enfant concerne, la date planifiee et les actions disponibles.">
        <div class="overflow-hidden rounded-2xl border border-slate-200">
            <div class="overflow-x-auto">
                <table class="app-table">
                    <thead>
                        <tr>
                            <th>Parent</th>
                            <th>Demande</th>
                            <th>Enfant</th>
                            <th>Date prevue</th>
                            <th>Statut</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            @php
                                $status = $item->normalized_status ?? 'pending';
                                $scheduled = $item->scheduled_for;
                                $badgeVariant = match($status) {
                                    'approved' => 'success',
                                    'completed' => 'info',
                                    'rejected' => 'danger',
                                    default => 'warning',
                                };
                            @endphp

                            <tr>
                                <td>
                                    <p class="font-semibold text-slate-900">{{ optional($item->parentUser)->name ?? $item->parent_name ?? 'Parent inconnu' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $item->parent_phone ?: 'Telephone non renseigne' }}</p>
                                </td>
                                <td>
                                    <p class="font-semibold text-slate-900">{{ $item->title }}</p>
                                    @if($item->message)
                                        <p class="mt-1 max-w-md text-xs leading-5 text-slate-500">{{ \Illuminate\Support\Str::limit($item->message, 110) }}</p>
                                    @endif
                                </td>
                                <td>
                                    @if($item->student)
                                        <p class="font-medium text-slate-900">{{ $item->student->full_name }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ $item->student->classroom?->name ?? 'Sans classe' }}</p>
                                    @else
                                        <span class="text-xs text-slate-500">Aucun enfant</span>
                                    @endif
                                </td>
                                <td>{{ $scheduled ? $scheduled->format('d/m/Y H:i') : 'Non planifie' }}</td>
                                <td><x-ui.badge :variant="$badgeVariant">{{ ucfirst($status) }}</x-ui.badge></td>
                                <td class="text-right">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <x-ui.button :href="route($routePrefix . '.show', $item)" variant="ghost" size="sm">Voir</x-ui.button>
                                        @if($canEdit)
                                            <x-ui.button :href="route($routePrefix . '.edit', $item)" variant="secondary" size="sm">Modifier</x-ui.button>
                                        @endif
                                        @if($status === 'pending' && $canApprove)
                                            <form method="POST" action="{{ route($routePrefix . '.approve', $item) }}">
                                                @csrf
                                                <x-ui.button type="submit" variant="outline" size="sm">Approuver</x-ui.button>
                                            </form>
                                            <form method="POST" action="{{ route($routePrefix . '.reject', $item) }}">
                                                @csrf
                                                <x-ui.button type="submit" variant="danger" size="sm">Refuser</x-ui.button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center text-sm text-slate-500">
                                    Aucun rendez-vous trouve pour ces filtres.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $items->links() }}
        </div>
    </x-ui.card>
</x-dynamic-component>
