<x-school-life-layout title="Operations transport" subtitle="Pointage rapide des montees et descentes pour la journee en cours.">
    <x-ui.page-header title="Pointage transport" subtitle="Un scan operateur simple pour enregistrer la montee et la descente des eleves.">
        <x-slot name="actions">
            <x-ui.button :href="route('transport.ops.index')" variant="secondary">Actualiser</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.card title="Filtres" subtitle="Affinez la liste des affectations actives a traiter.">
        <form method="GET" class="grid gap-3 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto]">
            <select name="route_id" class="app-input">
                <option value="">Toutes les routes</option>
                @foreach($routes as $route)
                    <option value="{{ $route->id }}" @selected((int) $routeId === (int) $route->id)>{{ $route->route_name }}</option>
                @endforeach
            </select>
            <select name="vehicle_id" class="app-input">
                <option value="">Tous les vehicules</option>
                @foreach($vehicles as $vehicle)
                    <option value="{{ $vehicle->id }}" @selected((int) $vehicleId === (int) $vehicle->id)>
                        {{ $vehicle->name ?: $vehicle->registration_number }}
                    </option>
                @endforeach
            </select>
            <x-ui.button type="submit" variant="primary">Filtrer</x-ui.button>
        </form>
    </x-ui.card>

    <section class="grid gap-4 xl:grid-cols-[minmax(0,1.4fr)_360px]">
        <x-ui.card title="Affectations du jour" subtitle="Enregistrez une seule montee et une seule descente par eleve et par jour.">
            <div class="space-y-3">
                @forelse($assignments as $assignment)
                    @php($todayBoarded = $assignment->transportLogs->firstWhere('status', \App\Models\TransportLog::STATUS_BOARDED))
                    @php($todayDropped = $assignment->transportLogs->firstWhere('status', \App\Models\TransportLog::STATUS_DROPPED))
                    <article class="rounded-2xl border border-slate-200 bg-white px-4 py-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold text-slate-900">{{ $assignment->student?->full_name ?? '-' }}</p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $assignment->student?->classroom?->name ?? '-' }}
                                    <span class="mx-1 text-slate-300">|</span>
                                    {{ $assignment->route?->route_name ?? '-' }}
                                    <span class="mx-1 text-slate-300">|</span>
                                    {{ $assignment->vehicle?->name ?: ($assignment->vehicle?->registration_number ?? '-') }}
                                </p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <x-ui.badge :variant="$todayBoarded ? 'success' : 'warning'">
                                    {{ $todayBoarded ? 'Montee OK' : 'Montee en attente' }}
                                </x-ui.badge>
                                <x-ui.badge :variant="$todayDropped ? 'info' : 'warning'">
                                    {{ $todayDropped ? 'Descente OK' : 'Descente en attente' }}
                                </x-ui.badge>
                            </div>
                        </div>

                        <div class="mt-4 grid gap-3 xl:grid-cols-2">
                            <form method="POST" action="{{ route('transport.ops.store') }}" class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-3 space-y-2">
                                @csrf
                                <input type="hidden" name="transport_assignment_id" value="{{ $assignment->id }}">
                                <input type="hidden" name="status" value="{{ \App\Models\TransportLog::STATUS_BOARDED }}">
                                <select name="route_stop_id" class="app-input">
                                    <option value="">Arret montee (optionnel)</option>
                                    @foreach(($assignment->route?->stops ?? collect())->sortBy('stop_order') as $stop)
                                        <option value="{{ $stop->id }}">{{ $stop->stop_order }}. {{ $stop->name }}</option>
                                    @endforeach
                                </select>
                                <input type="text" name="note" class="app-input" placeholder="Note montee (optionnel)">
                                <x-ui.button type="submit" variant="primary" size="sm">Enregistrer montee</x-ui.button>
                            </form>

                            <form method="POST" action="{{ route('transport.ops.store') }}" class="rounded-xl border border-sky-200 bg-sky-50 px-3 py-3 space-y-2">
                                @csrf
                                <input type="hidden" name="transport_assignment_id" value="{{ $assignment->id }}">
                                <input type="hidden" name="status" value="{{ \App\Models\TransportLog::STATUS_DROPPED }}">
                                <select name="route_stop_id" class="app-input">
                                    <option value="">Arret descente (optionnel)</option>
                                    @foreach(($assignment->route?->stops ?? collect())->sortBy('stop_order') as $stop)
                                        <option value="{{ $stop->id }}">{{ $stop->stop_order }}. {{ $stop->name }}</option>
                                    @endforeach
                                </select>
                                <input type="text" name="note" class="app-input" placeholder="Note descente (optionnel)">
                                <x-ui.button type="submit" variant="secondary" size="sm">Enregistrer descente</x-ui.button>
                            </form>
                        </div>
                    </article>
                @empty
                    <div class="student-empty">Aucune affectation active pour les filtres selectionnes.</div>
                @endforelse
            </div>
            <div class="mt-4">{{ $assignments->links() }}</div>
        </x-ui.card>

        <x-ui.card title="Journal recent" subtitle="Dernieres operations de la journee.">
            <div class="space-y-2">
                @forelse($recentLogs as $log)
                    <article class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-3">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-sm font-semibold text-slate-900">{{ $log->student?->full_name ?? '-' }}</p>
                            <x-ui.badge :variant="$log->status === \App\Models\TransportLog::STATUS_BOARDED ? 'success' : 'info'">
                                {{ $log->status === \App\Models\TransportLog::STATUS_BOARDED ? 'Montee' : 'Descente' }}
                            </x-ui.badge>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">
                            {{ $log->logged_at?->format('H:i') }} | {{ $log->route?->route_name ?? '-' }} | {{ $log->vehicle?->name ?: ($log->vehicle?->registration_number ?? '-') }}
                        </p>
                    </article>
                @empty
                    <div class="student-empty">Aucun enregistrement transport pour aujourd hui.</div>
                @endforelse
            </div>
        </x-ui.card>
    </section>
</x-school-life-layout>
