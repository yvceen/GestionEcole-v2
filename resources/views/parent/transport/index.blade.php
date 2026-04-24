<x-parent-layout title="Transport scolaire" subtitle="Suivez le circuit de transport de vos enfants et les derniers pointages montee/descente.">
    <section class="grid gap-4 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]">
        <x-ui.card title="Affectations transport" subtitle="Informations de route, vehicule et points d arret.">
            <div class="space-y-3">
                @forelse($children as $child)
                    @php($assignment = $child->transportAssignment)
                    <article class="rounded-2xl border border-slate-200 bg-white px-4 py-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold text-slate-900">{{ $child->full_name }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $child->classroom?->name ?? '-' }}</p>
                            </div>
                            @if($assignment && $assignment->is_active)
                                <x-ui.badge variant="success">Actif</x-ui.badge>
                            @else
                                <x-ui.badge variant="warning">Non assigne</x-ui.badge>
                            @endif
                        </div>

                        @if($assignment && $assignment->route)
                            <div class="mt-3 text-sm text-slate-600">
                                <p>Route: <span class="font-semibold text-slate-900">{{ $assignment->route->route_name }}</span></p>
                                <p>Vehicule:
                                    <span class="font-semibold text-slate-900">
                                        {{ $assignment->vehicle?->name ?: ($assignment->vehicle?->registration_number ?? '-') }}
                                    </span>
                                </p>
                                @if($assignment->vehicle?->driver?->name)
                                    <p>Chauffeur: <span class="font-semibold text-slate-900">{{ $assignment->vehicle->driver->name }}</span></p>
                                @endif
                                @if($assignment->pickup_point)
                                    <p>Point montee: <span class="font-semibold text-slate-900">{{ $assignment->pickup_point }}</span></p>
                                @endif
                            </div>

                            @if($assignment->route->stops->isNotEmpty())
                                <div class="mt-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-3">
                                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Arrets</p>
                                    <div class="mt-2 space-y-1 text-sm text-slate-700">
                                        @foreach($assignment->route->stops->sortBy('stop_order') as $stop)
                                            <p>
                                                {{ $stop->stop_order }}. {{ $stop->name }}
                                                @if($stop->scheduled_time)
                                                    <span class="text-slate-500">({{ \Illuminate\Support\Carbon::parse($stop->scheduled_time)->format('H:i') }})</span>
                                                @endif
                                            </p>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @else
                            <p class="mt-3 text-sm text-slate-500">Aucune affectation transport active.</p>
                        @endif
                    </article>
                @empty
                    <div class="student-empty">Aucun enfant lie a ce compte.</div>
                @endforelse
            </div>
        </x-ui.card>

        <x-ui.card title="Derniers pointages" subtitle="12 derniers evenements montee/descente recents.">
            <div class="space-y-2">
                @forelse($logs as $log)
                    <article class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-semibold text-slate-900">{{ $log->student?->full_name ?? '-' }}</p>
                            <x-ui.badge :variant="$log->status === \App\Models\TransportLog::STATUS_BOARDED ? 'success' : 'info'">
                                {{ $log->status === \App\Models\TransportLog::STATUS_BOARDED ? 'Montee' : 'Descente' }}
                            </x-ui.badge>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">
                            {{ $log->logged_at?->format('d/m/Y H:i') }} | {{ $log->route?->route_name ?? '-' }}
                        </p>
                    </article>
                @empty
                    <div class="student-empty">Aucun pointage transport recent.</div>
                @endforelse
            </div>
        </x-ui.card>
    </section>
</x-parent-layout>
