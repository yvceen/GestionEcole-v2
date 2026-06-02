<x-admin-layout title="Affectations de transport">
    <x-ui.page-header
        title="Affectations de transport"
        subtitle="Suivez les Élèves reliés a chaque route avec le vehicule, le conducteur et le point de ramassage."
    >
        <x-slot name="actions">
            <x-ui.button :href="route('admin.transport.assignments.create')" variant="primary">Ajouter une affectation</x-ui.button>
            <x-ui.button :href="route('admin.transport.routes.index')" variant="secondary">Routes</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    @if(session('success'))
        <x-ui.alert variant="success">{{ session('success') }}</x-ui.alert>
    @endif

    <div class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 bg-gradient-to-r from-sky-50 via-white to-emerald-50 px-6 py-5">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-sky-700">Liste active</p>
            <h2 class="mt-2 text-xl font-bold text-slate-950">Élèves affectés au transport</h2>
        </div>

        <div class="hidden border-b border-slate-200 bg-slate-50 px-6 py-3 text-xs font-bold uppercase tracking-[0.14em] text-slate-500 xl:grid xl:grid-cols-[minmax(220px,1.1fr)_minmax(220px,1fr)_minmax(220px,1fr)_minmax(180px,0.85fr)_130px_220px] xl:gap-5">
            <div>Élève</div>
            <div>Route</div>
            <div>Vehicule</div>
            <div>Ramassage</div>
            <div>Statut</div>
            <div class="text-right">Actions</div>
        </div>

        <div class="divide-y divide-slate-100">
            @forelse($assignments as $assignment)
                <article class="px-5 py-5 transition hover:bg-sky-50/35 xl:grid xl:grid-cols-[minmax(220px,1.1fr)_minmax(220px,1fr)_minmax(220px,1fr)_minmax(180px,0.85fr)_130px_220px] xl:items-center xl:gap-5 xl:px-6">
                    <div>
                        <p class="xl:hidden text-[0.65rem] font-bold uppercase tracking-[0.14em] text-slate-400">Élève</p>
                        <p class="font-semibold text-slate-950">{{ $assignment->student?->full_name ?? '-' }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $assignment->student?->classroom?->name ?? '-' }}</p>
                    </div>

                    <div class="mt-4 xl:mt-0">
                        <p class="xl:hidden text-[0.65rem] font-bold uppercase tracking-[0.14em] text-slate-400">Route</p>
                        <p class="font-semibold text-slate-900">{{ $assignment->route?->route_name ?? '-' }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ ucfirst((string) ($assignment->period ?? 'both')) }} - {{ optional($assignment->assigned_date)->format('d/m/Y') }}</p>
                    </div>

                    <div class="mt-4 xl:mt-0">
                        <p class="xl:hidden text-[0.65rem] font-bold uppercase tracking-[0.14em] text-slate-400">Vehicule</p>
                        <p class="font-semibold text-slate-900">{{ $assignment->vehicle?->name ?: ($assignment->route?->vehicle?->name ?? $assignment->route?->vehicle?->registration_number ?? '-') }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $assignment->vehicle?->driver?->name ?: ($assignment->route?->vehicle?->driver?->name ?? 'Conducteur non renseigne') }}</p>
                    </div>

                    <div class="mt-4 text-sm text-slate-600 xl:mt-0">
                        <p class="xl:hidden text-[0.65rem] font-bold uppercase tracking-[0.14em] text-slate-400">Ramassage</p>
                        {{ $assignment->pickup_point ?: '-' }}
                    </div>

                    <div class="mt-4 xl:mt-0">
                        <x-ui.badge :variant="$assignment->is_active && !$assignment->ended_date ? 'success' : 'warning'">
                            {{ $assignment->is_active && !$assignment->ended_date ? 'Actif' : 'Termine' }}
                        </x-ui.badge>
                    </div>

                    <div class="mt-5 flex flex-wrap gap-2 xl:mt-0 xl:justify-end">
                        <x-ui.button :href="route('admin.transport.assignments.show', $assignment)" variant="secondary" size="sm">Voir</x-ui.button>
                        <x-ui.button :href="route('admin.transport.assignments.edit', $assignment)" variant="ghost" size="sm">Modifier</x-ui.button>
                        <form method="POST" action="{{ route('admin.transport.assignments.destroy', $assignment) }}" onsubmit="return confirm('Supprimer cette affectation ?')">
                            @csrf
                            @method('DELETE')
                            <x-ui.button type="submit" variant="danger" size="sm">Supprimer</x-ui.button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="px-6 py-12 text-center text-slate-500">Aucune affectation enregistrée.</div>
            @endforelse
        </div>

        <div class="border-t border-slate-200 p-4">{{ $assignments->links() }}</div>
    </div>
</x-admin-layout>
