<x-admin-layout title="Affectations de transport">
    <x-ui.page-header
        title="Affectations de transport"
        subtitle="Suivez les eleves relies a chaque route avec le vehicule, le conducteur et le point de ramassage."
    >
        <x-slot name="actions">
            <x-ui.button :href="route('admin.transport.assignments.create')" variant="primary">Ajouter une affectation</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    @if(session('success'))
        <x-ui.alert variant="success">{{ session('success') }}</x-ui.alert>
    @endif

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[980px] text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-left text-xs uppercase tracking-[0.14em] text-slate-500">
                        <th class="px-6 py-4">Eleve</th>
                        <th class="px-6 py-4">Route</th>
                        <th class="px-6 py-4">Vehicule / conducteur</th>
                        <th class="px-6 py-4">Point de ramassage</th>
                        <th class="px-6 py-4">Date debut</th>
                        <th class="px-6 py-4">Statut</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($assignments as $assignment)
                        <tr>
                            <td class="px-6 py-4">
                                <div class="font-semibold text-slate-900">{{ $assignment->student?->full_name ?? '-' }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $assignment->student?->classroom?->name ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-semibold text-slate-900">{{ $assignment->route?->route_name ?? '-' }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ ucfirst((string) ($assignment->period ?? 'both')) }}</div>
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-500">
                                <div>{{ $assignment->vehicle?->name ?: ($assignment->route?->vehicle?->name ?? '-') }}</div>
                                <div>{{ $assignment->vehicle?->driver?->name ?: ($assignment->route?->vehicle?->driver?->name ?? 'Conducteur non renseigne') }}</div>
                            </td>
                            <td class="px-6 py-4">{{ $assignment->pickup_point ?: '-' }}</td>
                            <td class="px-6 py-4">{{ optional($assignment->assigned_date)->format('d/m/Y') }}</td>
                            <td class="px-6 py-4">
                                <x-ui.badge :variant="$assignment->is_active && !$assignment->ended_date ? 'success' : 'warning'">
                                    {{ $assignment->is_active && !$assignment->ended_date ? 'Actif' : 'Termine' }}
                                </x-ui.badge>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex justify-end gap-2">
                                    <x-ui.button :href="route('admin.transport.assignments.show', $assignment)" variant="secondary" size="sm">Voir</x-ui.button>
                                    <x-ui.button :href="route('admin.transport.assignments.edit', $assignment)" variant="ghost" size="sm">Modifier</x-ui.button>
                                    <form method="POST" action="{{ route('admin.transport.assignments.destroy', $assignment) }}" onsubmit="return confirm('Supprimer cette affectation ?')">
                                        @csrf
                                        @method('DELETE')
                                        <x-ui.button type="submit" variant="danger" size="sm">Supprimer</x-ui.button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500">Aucune affectation enregistree.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $assignments->links() }}</div>
    </div>
</x-admin-layout>
