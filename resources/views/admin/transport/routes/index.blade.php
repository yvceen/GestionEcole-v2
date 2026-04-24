<x-admin-layout title="Routes de transport">
    <x-ui.page-header
        title="Routes de transport"
        subtitle="Visualisez l etat de chaque circuit avec vehicule, conducteur, arrets et volume eleves."
    >
        <x-slot name="actions">
            <x-ui.button :href="route('admin.transport.routes.create')" variant="primary">Ajouter une route</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    @if(session('success'))
        <x-ui.alert variant="success">{{ session('success') }}</x-ui.alert>
    @endif

    <div class="grid gap-5 xl:grid-cols-2">
        @forelse($routes as $route)
            <x-ui.card :title="$route->route_name" :subtitle="$route->start_point.' -> '.$route->end_point">
                <div class="space-y-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <x-ui.badge :variant="$route->is_active ? 'success' : 'warning'">{{ $route->is_active ? 'Actif' : 'Inactif' }}</x-ui.badge>
                        <x-ui.badge variant="info">{{ (int) $route->active_assignments_count }} eleve(s)</x-ui.badge>
                        <x-ui.badge variant="warning">{{ (int) $route->stops_count }} arret(s)</x-ui.badge>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 text-sm text-slate-600">
                        <div>
                            <p class="text-slate-500">Vehicule</p>
                            <p class="font-semibold text-slate-900">{{ $route->vehicle?->name ?: ($route->vehicle?->registration_number ?? 'Non assigne') }}</p>
                            @if($route->vehicle?->driver?->name)
                                <p class="mt-1 text-xs text-slate-500">{{ $route->vehicle->driver->name }}{{ $route->vehicle->driver->phone ? ' • '.$route->vehicle->driver->phone : '' }}</p>
                            @endif
                        </div>
                        <div>
                            <p class="text-slate-500">Distance / duree</p>
                            <p class="font-semibold text-slate-900">{{ $route->distance_km ?? '-' }} km • {{ $route->estimated_minutes ?? '-' }} min</p>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2">
                        <x-ui.button :href="route('admin.transport.routes.show', $route)" variant="secondary" size="sm">Voir</x-ui.button>
                        <x-ui.button :href="route('admin.transport.routes.edit', $route)" variant="ghost" size="sm">Modifier</x-ui.button>
                        <form method="POST" action="{{ route('admin.transport.routes.destroy', $route) }}" onsubmit="return confirm('Supprimer cette route ?')">
                            @csrf
                            @method('DELETE')
                            <x-ui.button type="submit" variant="danger" size="sm">Supprimer</x-ui.button>
                        </form>
                    </div>
                </div>
            </x-ui.card>
        @empty
            <x-ui.card title="Aucune route" subtitle="Commencez par creer un premier circuit de transport.">
                <p class="text-sm text-slate-500">Les arrets, le conducteur et les affectations eleves apparaitront ensuite ici.</p>
            </x-ui.card>
        @endforelse
    </div>

    <div class="mt-4">{{ $routes->links() }}</div>
</x-admin-layout>
