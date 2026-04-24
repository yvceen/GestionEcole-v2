<x-admin-layout title="Gestion des Véhicules">
    <div class="max-w-7xl mx-auto">
        @if(session('success'))
            <div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                {{ session('success') }}
            </div>
        @endif

        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-3xl font-semibold text-slate-900">🚌 Véhicules de Transport</h1>
                <p class="mt-1 text-sm text-slate-600">Gérez les véhicules, conducteurs et affectations.</p>
            </div>
            <a href="{{ route('admin.transport.vehicles.create') }}"
               class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-6 py-3 text-sm font-semibold text-white transition hover:bg-black">
                + Ajouter Véhicule
            </a>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Vehicle</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Immatriculation</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Conducteur</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Capacité</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Statut</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($vehicles ?? collect()) as $vehicle)
                            <tr class="border-b border-slate-200 hover:bg-slate-50 transition">
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-slate-900">{{ $vehicle->name ?: $vehicle->vehicle_type }}</div>
                                    <div class="text-xs text-slate-500">{{ $vehicle->color }} • {{ $vehicle->model_year }}</div>
                                </td>
                                <td class="px-6 py-4 font-mono text-slate-900">{{ $vehicle->registration_number }}</td>
                                <td class="px-6 py-4">
                                    {{ $vehicle->driver?->name ?? '—' }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">
                                        {{ $vehicle->capacity }} sièges
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    @if($vehicle->is_active)
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                                            ✓ Actif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                                            Inactif
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.transport.vehicles.show', $vehicle) }}"
                                           class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                            Voir
                                        </a>
                                        <a href="{{ route('admin.transport.vehicles.edit', $vehicle) }}"
                                           class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                            Éditer
                                        </a>
                                        <form method="POST" action="{{ route('admin.transport.vehicles.destroy', $vehicle) }}"
                                              onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce véhicule ?');"
                                              class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-100">
                                                Supprimer
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 px-6 text-center text-slate-600">
                                    Aucun véhicule enregistré.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($vehicles->hasPages())
                <div class="p-4 border-t border-slate-200">
                    {{ $vehicles->links() }}
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
