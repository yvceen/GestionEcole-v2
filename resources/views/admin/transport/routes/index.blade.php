<x-admin-layout title="Routes de transport">
    <x-ui.page-header
        title="Routes de transport"
        subtitle="Visualisez chaque circuit avec son vehicule, ses arrêts et les Élèves affectés."
    >
        <x-slot name="actions">
            <x-ui.button :href="route('admin.transport.routes.create')" variant="primary">Ajouter une route</x-ui.button>
            <x-ui.button :href="route('admin.transport.assignments.create')" variant="secondary">Affecter des Élèves</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    @if(session('success'))
        <x-ui.alert variant="success">{{ session('success') }}</x-ui.alert>
    @endif

    <div class="grid gap-5 xl:grid-cols-2">
        @forelse($routes as $route)
            <article class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:border-sky-200 hover:shadow-md">
                <div class="h-2 bg-gradient-to-r {{ $route->is_active ? 'from-emerald-400 via-sky-400 to-indigo-500' : 'from-slate-300 via-slate-200 to-slate-300' }}"></div>
                <div class="p-5">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.18em] text-sky-700">Circuit</p>
                            <h2 class="mt-2 text-xl font-bold text-slate-950">{{ $route->route_name }}</h2>
                            <p class="mt-1 text-sm text-slate-500">{{ $route->start_point }} -> {{ $route->end_point }}</p>
                        </div>
                        <x-ui.badge :variant="$route->is_active ? 'success' : 'warning'">{{ $route->is_active ? 'Actif' : 'Inactif' }}</x-ui.badge>
                    </div>

                    <div class="mt-5 grid gap-3 sm:grid-cols-3">
                        <div class="rounded-2xl border border-sky-100 bg-sky-50/70 p-3">
                            <p class="text-xs font-bold uppercase tracking-[0.14em] text-sky-700">Élèves</p>
                            <p class="mt-2 text-2xl font-bold text-slate-950">{{ (int) $route->active_assignments_count }}</p>
                        </div>
                        <div class="rounded-2xl border border-emerald-100 bg-emerald-50/70 p-3">
                            <p class="text-xs font-bold uppercase tracking-[0.14em] text-emerald-700">Arrêts</p>
                            <p class="mt-2 text-2xl font-bold text-slate-950">{{ (int) $route->stops_count }}</p>
                        </div>
                        <div class="rounded-2xl border border-indigo-100 bg-indigo-50/70 p-3">
                            <p class="text-xs font-bold uppercase tracking-[0.14em] text-indigo-700">Duree</p>
                            <p class="mt-2 text-2xl font-bold text-slate-950">{{ $route->estimated_minutes ?? '-' }}<span class="text-sm font-semibold text-slate-500"> min</span></p>
                        </div>
                    </div>

                    <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                        <div class="flex flex-wrap justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Vehicule</p>
                                <p class="mt-1 font-semibold text-slate-900">{{ $route->vehicle?->name ?: ($route->vehicle?->registration_number ?? 'Non assigne') }}</p>
                            </div>
                            <div class="text-left sm:text-right">
                                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Conducteur</p>
                                <p class="mt-1 font-semibold text-slate-900">{{ $route->vehicle?->driver?->name ?? 'Non renseigne' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 flex flex-wrap justify-end gap-2">
                        <x-ui.button :href="route('admin.transport.routes.show', $route)" variant="secondary" size="sm">Voir</x-ui.button>
                        <x-ui.button :href="route('admin.transport.routes.edit', $route)" variant="ghost" size="sm">Modifier</x-ui.button>
                        <form method="POST" action="{{ route('admin.transport.routes.destroy', $route) }}" onsubmit="return confirm('Supprimer cette route ?')">
                            @csrf
                            @method('DELETE')
                            <x-ui.button type="submit" variant="danger" size="sm">Supprimer</x-ui.button>
                        </form>
                    </div>
                </div>
            </article>
        @empty
            <x-ui.card title="Aucune route" subtitle="Commencez par creer un premier circuit de transport.">
                <p class="text-sm text-slate-500">Les arrêts, le conducteur et les affectations Élèves apparaitront ensuite ici.</p>
            </x-ui.card>
        @endforelse
    </div>

    <div class="mt-4">{{ $routes->links() }}</div>
</x-admin-layout>
