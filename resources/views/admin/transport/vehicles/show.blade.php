<x-admin-layout title="Détail du véhicule">
    <x-ui.page-header
        :title="ucfirst($vehicle->vehicle_type)"
        :subtitle="$vehicle->registration_number"
    >
        <x-slot name="actions">
            <x-ui.button :href="route('admin.transport.vehicles.edit', $vehicle)" variant="primary">
                Modifier
            </x-ui.button>
            <x-ui.button :href="route('admin.transport.vehicles.index')" variant="secondary">
                Retour
            </x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1.4fr)_320px]">
        <div class="space-y-6">
            <x-ui.card title="Informations générales">
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4"><span class="text-slate-500">Nom</span><span class="font-semibold text-slate-900">{{ $vehicle->name ?? '—' }}</span></div>
                    <div class="flex justify-between gap-4"><span class="text-slate-500">Type</span><span class="font-semibold text-slate-900">{{ ucfirst($vehicle->vehicle_type) }}</span></div>
                    <div class="flex justify-between gap-4"><span class="text-slate-500">Immatriculation</span><span class="font-mono font-semibold text-slate-900">{{ $vehicle->registration_number }}</span></div>
                    <div class="flex justify-between gap-4"><span class="text-slate-500">Plaque minéralogique</span><span class="font-mono text-slate-900">{{ $vehicle->plate_number ?? '—' }}</span></div>
                    <div class="flex justify-between gap-4"><span class="text-slate-500">Capacité</span><span class="font-semibold text-slate-900">{{ $vehicle->capacity }} places</span></div>
                    <div class="flex justify-between gap-4"><span class="text-slate-500">Couleur</span><span class="text-slate-900">{{ $vehicle->color ?? '—' }}</span></div>
                    <div class="flex justify-between gap-4"><span class="text-slate-500">Année</span><span class="text-slate-900">{{ $vehicle->model_year ?? '—' }}</span></div>
                </div>
            </x-ui.card>

            <x-ui.card title="Conducteur">
                @if($vehicle->driver)
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-semibold text-slate-900">{{ $vehicle->driver->name }}</p>
                            <p class="text-sm text-slate-500">{{ $vehicle->driver->email }}</p>
                            @if($vehicle->assistant_name)
                                <p class="mt-1 text-sm text-slate-500">Assistant: {{ $vehicle->assistant_name }}</p>
                            @endif
                        </div>
                        <x-ui.badge variant="success">Assigné</x-ui.badge>
                    </div>
                @else
                    <p class="text-sm text-slate-500">
                        Aucun conducteur assigne.
                        @if($vehicle->assistant_name)
                            Assistant: {{ $vehicle->assistant_name }}.
                        @endif
                    </p>
                @endif
            </x-ui.card>

            @if($vehicle->notes)
                <x-ui.card title="Notes">
                    <p class="whitespace-pre-wrap text-sm leading-6 text-slate-700">{{ $vehicle->notes }}</p>
                </x-ui.card>
            @endif
        </div>

        <div class="space-y-6">
            <x-ui.card title="Statut">
                <x-ui.badge :variant="$vehicle->is_active ? 'success' : 'info'">
                    {{ $vehicle->is_active ? 'Actif' : 'Inactif' }}
                </x-ui.badge>
            </x-ui.card>

            <x-ui.card title="Routes assignées">
                @if($vehicle->routes->count() > 0)
                    <div class="space-y-2">
                        @foreach($vehicle->routes as $route)
                            <a href="{{ route('admin.transport.routes.show', $route) }}" class="block rounded-xl border border-slate-200 px-3 py-3 hover:bg-slate-50">
                                <div class="font-semibold text-slate-900">{{ $route->route_name }}</div>
                                <div class="text-xs text-slate-500">{{ $route->start_point }} → {{ $route->end_point }}</div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-slate-500">Aucune route assignée.</p>
                @endif
            </x-ui.card>

            <x-ui.card title="Dates">
                <div class="space-y-3 text-sm">
                    <div><span class="text-slate-500">Créé</span><div class="text-slate-900">{{ optional($vehicle->created_at)->format('d/m/Y H:i') }}</div></div>
                    <div><span class="text-slate-500">Modifié</span><div class="text-slate-900">{{ optional($vehicle->updated_at)->format('d/m/Y H:i') }}</div></div>
                </div>
            </x-ui.card>
        </div>
    </div>
</x-admin-layout>
