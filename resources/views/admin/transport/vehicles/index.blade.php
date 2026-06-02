<x-admin-layout title="Gestion des véhicules">
    <x-ui.page-header
        title="Véhicules de transport"
        subtitle="Gerez la flotte, les conducteurs et la disponibilite des véhicules."
    >
        <x-slot name="actions">
            <x-ui.button :href="route('admin.transport.vehicles.create')" variant="primary">Ajouter vehicule</x-ui.button>
            <x-ui.button :href="route('admin.transport.index')" variant="secondary">Transport</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    @if(session('success'))
        <x-ui.alert variant="success">{{ session('success') }}</x-ui.alert>
    @endif

    <div class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 bg-gradient-to-r from-sky-50 via-white to-indigo-50 px-6 py-5">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-sky-700">Flotte active</p>
            <h2 class="mt-2 text-xl font-bold text-slate-950">Véhicules et conducteurs</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[940px] text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-left text-xs font-bold uppercase tracking-[0.14em] text-slate-500">
                        <th class="px-6 py-4">Vehicule</th>
                        <th class="px-6 py-4">Immatriculation</th>
                        <th class="px-6 py-4">Conducteur</th>
                        <th class="px-6 py-4">Capacite</th>
                        <th class="px-6 py-4">Statut</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse(($vehicles ?? collect()) as $vehicle)
                        <tr class="transition hover:bg-sky-50/40">
                            <td class="px-6 py-4">
                                <div class="font-semibold text-slate-950">{{ $vehicle->name ?: $vehicle->vehicle_type }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $vehicle->color ?: '-' }} - {{ $vehicle->model_year ?: '-' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-1 font-mono text-slate-900">{{ $vehicle->registration_number }}</span>
                                <div class="mt-2 text-xs text-slate-500">{{ $vehicle->plate_number ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-semibold text-slate-900">{{ $vehicle->driver?->name ?? 'Non renseigne' }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $vehicle->driver?->phone ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-700">
                                    {{ $vehicle->capacity }} sieges
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <x-ui.badge :variant="$vehicle->is_active ? 'success' : 'warning'">
                                    {{ $vehicle->is_active ? 'Actif' : 'Inactif' }}
                                </x-ui.badge>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <x-ui.button :href="route('admin.transport.vehicles.show', $vehicle)" variant="secondary" size="sm">Voir</x-ui.button>
                                    <x-ui.button :href="route('admin.transport.vehicles.edit', $vehicle)" variant="ghost" size="sm">Modifier</x-ui.button>
                                    <form method="POST" action="{{ route('admin.transport.vehicles.destroy', $vehicle) }}" onsubmit="return confirm('Supprimer ce vehicule ?')">
                                        @csrf
                                        @method('DELETE')
                                        <x-ui.button type="submit" variant="danger" size="sm">Supprimer</x-ui.button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500">Aucun vehicule enregistré.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($vehicles->hasPages())
            <div class="border-t border-slate-200 p-4">
                {{ $vehicles->links() }}
            </div>
        @endif
    </div>
</x-admin-layout>
