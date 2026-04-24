<x-admin-layout title="Transport scolaire">
    <x-ui.page-header
        title="Transport scolaire"
        subtitle="Routes, vehicules, conducteurs et affectations eleves sont reunis dans un tableau de bord plus exploitable."
    />

    <section class="grid gap-4 sm:grid-cols-4">
        <article class="app-stat-card">
            <p class="app-stat-label">Vehicules</p>
            <p class="app-stat-value">{{ $vehiclesCount }}</p>
            <p class="app-stat-meta">{{ $activeVehiclesCount ?? 0 }} actifs</p>
        </article>
        <article class="app-stat-card">
            <p class="app-stat-label">Circuits</p>
            <p class="app-stat-value">{{ $routesCount }}</p>
            <p class="app-stat-meta">Routes configurees pour l ecole</p>
        </article>
        <article class="app-stat-card">
            <p class="app-stat-label">Affectations</p>
            <p class="app-stat-value">{{ $assignmentsCount }}</p>
            <p class="app-stat-meta">Eleves rattaches au transport</p>
        </article>
        <article class="app-stat-card">
            <p class="app-stat-label">Pilotage</p>
            <p class="app-stat-value">{{ ($routes ?? collect())->sum('active_assignments_count') }}</p>
            <p class="app-stat-meta">Places eleves sur les routes visibles</p>
        </article>
    </section>

    <section class="grid gap-6 xl:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)]">
        <x-ui.card title="Circuits prioritaires" subtitle="Vue rapide des vehicules, du conducteur, des horaires d arret et du nombre d eleves.">
            <div class="space-y-4">
                @forelse($routes as $route)
                    <article class="rounded-2xl border border-slate-200 bg-white px-4 py-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold text-slate-900">{{ $route->route_name }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $route->start_point }} -> {{ $route->end_point }}</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <x-ui.badge :variant="$route->is_active ? 'success' : 'warning'">{{ $route->is_active ? 'Actif' : 'Inactif' }}</x-ui.badge>
                                <x-ui.badge variant="info">{{ (int) $route->active_assignments_count }} eleve(s)</x-ui.badge>
                                <x-ui.badge variant="warning">{{ (int) $route->stops_count }} arret(s)</x-ui.badge>
                            </div>
                        </div>

                        <div class="mt-4 grid gap-3 md:grid-cols-2">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                                <p class="font-semibold text-slate-900">Vehicule</p>
                                <p>{{ $route->vehicle?->name ?: ($route->vehicle?->registration_number ?? 'Non assigne') }}</p>
                                @if($route->vehicle?->driver?->name)
                                    <p class="mt-1 text-xs text-slate-500">Conducteur : {{ $route->vehicle->driver->name }} {{ $route->vehicle->driver->phone ? '• '.$route->vehicle->driver->phone : '' }}</p>
                                @endif
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                                @php
                                    $orderedStops = $route->stops->sortBy('stop_order');
                                    $firstStop = $orderedStops->first();
                                    $lastStop = $orderedStops->last();
                                @endphp
                                <p class="font-semibold text-slate-900">Horaire indicatif</p>
                                <p>Matin : {{ $firstStop?->scheduled_time ? substr((string) $firstStop->scheduled_time, 0, 5) : 'Non defini' }}</p>
                                <p>Soir : {{ $lastStop?->scheduled_time ? substr((string) $lastStop->scheduled_time, 0, 5) : 'Non defini' }}</p>
                            </div>
                        </div>

                        <div class="mt-4 flex justify-end">
                            <x-ui.button :href="route('admin.transport.routes.show', $route)" variant="secondary">Ouvrir la route</x-ui.button>
                        </div>
                    </article>
                @empty
                    <div class="student-empty">Aucune route de transport configuree.</div>
                @endforelse
            </div>
        </x-ui.card>

        <div class="space-y-6">
            <x-ui.card title="Actions rapides" subtitle="Acces direct aux ecrans les plus utiles.">
                <div class="space-y-3">
                    <x-ui.button :href="route('admin.transport.vehicles.index')" variant="primary">Gerer les vehicules</x-ui.button>
                    <x-ui.button :href="route('admin.transport.routes.index')" variant="secondary">Gerer les routes</x-ui.button>
                    <x-ui.button :href="route('admin.transport.assignments.index')" variant="secondary">Gerer les affectations</x-ui.button>
                    <x-ui.button :href="route('admin.students.index')" variant="ghost">Ouvrir les fiches eleves</x-ui.button>
                </div>
            </x-ui.card>

            <x-ui.card title="Dernieres affectations" subtitle="Suivez les mouvements transport les plus recents.">
                <div class="space-y-3">
                    @forelse($recentAssignments as $assignment)
                        <article class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">{{ $assignment->student?->full_name ?? '-' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $assignment->student?->classroom?->name ?? '-' }} • {{ $assignment->route?->route_name ?? '-' }}</p>
                                </div>
                                <x-ui.badge :variant="$assignment->is_active ? 'success' : 'warning'">{{ $assignment->is_active ? 'Actif' : 'Termine' }}</x-ui.badge>
                            </div>
                        </article>
                    @empty
                        <div class="student-empty">Aucune affectation recente.</div>
                    @endforelse
                </div>
            </x-ui.card>
        </div>
    </section>
</x-admin-layout>
