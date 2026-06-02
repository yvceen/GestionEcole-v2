<x-chauffeur-layout title="Espace chauffeur" subtitle="Gestion claire des circuits, Élèves transportés et pointages du jour.">
    @php
        $statusLabels = [
            '' => 'Tous',
            'waiting' => 'En attente',
            'boarded' => 'A bord',
            'done' => 'Termines',
        ];
    @endphp

    <section class="overflow-hidden rounded-[32px] border border-sky-100 bg-[radial-gradient(circle_at_top_right,_rgba(14,165,233,0.20),_transparent_34%),radial-gradient(circle_at_bottom_left,_rgba(16,185,129,0.16),_transparent_34%),linear-gradient(135deg,#ffffff,#f8fbff_55%,#eefdf8)] px-6 py-6 shadow-xl shadow-slate-200/70 md:px-8">
        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_280px] lg:items-center">
            <div>
                <div class="inline-flex rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-sky-700">
                    Circuit du jour
                </div>
                <h1 class="mt-4 text-3xl font-semibold tracking-tight text-slate-950 md:text-4xl">Bonjour {{ $user->name }}</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                    Retrouvez les Élèves affectés a vos véhicules, confirmez les montées et les descentes, et gardez les parents informés.
                </p>
            </div>

            <div class="rounded-[28px] border border-white bg-white/85 px-5 py-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Véhicules liés</p>
                <p class="mt-3 text-4xl font-bold tracking-tight text-slate-950">{{ $vehicles->count() }}</p>
                <p class="mt-2 text-sm text-slate-500">{{ $routes->count() }} circuit(s) actif(s)</p>
            </div>
        </div>
    </section>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-[26px] border border-sky-100 bg-sky-50 px-5 py-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-sky-700">Élèves</p>
            <p class="mt-3 text-3xl font-bold text-slate-950">{{ $stats['students'] }}</p>
            <p class="mt-1 text-sm text-slate-500">Affectations filtrees</p>
        </div>
        <div class="rounded-[26px] border border-amber-100 bg-amber-50 px-5 py-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-amber-700">En attente</p>
            <p class="mt-3 text-3xl font-bold text-slate-950">{{ $stats['waiting'] }}</p>
            <p class="mt-1 text-sm text-slate-500">Pas encore a bord</p>
        </div>
        <div class="rounded-[26px] border border-emerald-100 bg-emerald-50 px-5 py-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">A bord</p>
            <p class="mt-3 text-3xl font-bold text-slate-950">{{ $stats['boarded'] }}</p>
            <p class="mt-1 text-sm text-slate-500">Montees confirmees</p>
        </div>
        <div class="rounded-[26px] border border-indigo-100 bg-indigo-50 px-5 py-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-indigo-700">Termines</p>
            <p class="mt-3 text-3xl font-bold text-slate-950">{{ $stats['dropped'] }}</p>
            <p class="mt-1 text-sm text-slate-500">Descentes confirmees</p>
        </div>
    </section>

    <section class="rounded-[28px] border border-slate-200 bg-white px-5 py-5 shadow-sm">
        <form method="GET" action="{{ route('chauffeur.dashboard') }}" class="grid gap-3 xl:grid-cols-[minmax(0,1.4fr)_220px_220px_190px_auto] xl:items-end">
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-800">Recherche</label>
                <input name="q" value="{{ $q }}" class="app-input" placeholder="Élève, classe, parent ou téléphone">
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-800">Vehicule</label>
                <select name="vehicle_id" class="app-input">
                    <option value="">Tous</option>
                    @foreach($vehicles as $vehicle)
                        <option value="{{ $vehicle->id }}" @selected($vehicleId === (int) $vehicle->id)>
                            {{ $vehicle->name ?: $vehicle->registration_number }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-800">Circuit</label>
                <select name="route_id" class="app-input">
                    <option value="">Tous</option>
                    @foreach($routes as $route)
                        <option value="{{ $route->id }}" @selected($routeId === (int) $route->id)>{{ $route->route_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-800">Etat</label>
                <select name="status" class="app-input">
                    @foreach($statusLabels as $key => $label)
                        <option value="{{ $key }}" @selected($status === (string) $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <x-ui.button type="submit" variant="primary">Filtrer</x-ui.button>
                <x-ui.button :href="route('chauffeur.dashboard')" variant="secondary">Reset</x-ui.button>
            </div>
        </form>
    </section>

    <section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_390px]">
        <div class="rounded-[30px] border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-6 py-5">
                <h2 class="text-2xl font-semibold tracking-tight text-slate-950">Élèves a transporter</h2>
                <p class="mt-1 text-sm text-slate-500">Un Élève peut avoir une montée et une descente par jour. Vous pouvez corriger l'arrêt ou la note si besoin.</p>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse($assignments as $assignment)
                    @php
                        $todayBoarded = $assignment->transportLogs->firstWhere('status', \App\Models\TransportLog::STATUS_BOARDED);
                        $todayDropped = $assignment->transportLogs->firstWhere('status', \App\Models\TransportLog::STATUS_DROPPED);
                        $stops = ($assignment->route?->stops ?? collect())->sortBy('stop_order');
                    @endphp
                    <article class="px-6 py-5">
                        <div class="grid gap-5 2xl:grid-cols-[minmax(0,1fr)_360px]">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-ui.badge :variant="$todayBoarded ? 'success' : 'warning'">
                                        {{ $todayBoarded ? 'Montee OK' : 'En attente' }}
                                    </x-ui.badge>
                                    <x-ui.badge :variant="$todayDropped ? 'info' : 'warning'">
                                        {{ $todayDropped ? 'Descente OK' : 'Descente a faire' }}
                                    </x-ui.badge>
                                    <span class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">{{ $assignment->student?->classroom?->name ?? '-' }}</span>
                                </div>

                                <h3 class="mt-3 text-xl font-semibold text-slate-950">{{ $assignment->student?->full_name ?? '-' }}</h3>
                                <p class="mt-1 text-sm text-slate-500">
                                    {{ $assignment->route?->route_name ?? '-' }}
                                    <span class="mx-1 text-slate-300">|</span>
                                    {{ $assignment->vehicle?->name ?: ($assignment->vehicle?->registration_number ?? '-') }}
                                    <span class="mx-1 text-slate-300">|</span>
                                    {{ $assignment->period === 'morning' ? 'Matin' : ($assignment->period === 'evening' ? 'Soir' : 'Matin et soir') }}
                                </p>

                                <div class="mt-4 grid gap-3 md:grid-cols-3">
                                    <div class="rounded-2xl bg-slate-50 px-4 py-3">
                                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Point</p>
                                        <p class="mt-1 text-sm font-semibold text-slate-950">{{ $assignment->pickup_point ?: 'Non renseigne' }}</p>
                                    </div>
                                    <div class="rounded-2xl bg-sky-50 px-4 py-3">
                                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-sky-700">Parent</p>
                                        <p class="mt-1 text-sm font-semibold text-slate-950">{{ $assignment->student?->parentUser?->name ?? '-' }}</p>
                                    </div>
                                    <div class="rounded-2xl bg-emerald-50 px-4 py-3">
                                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-emerald-700">Téléphone</p>
                                        @if($assignment->student?->parentUser?->phone)
                                            <a href="tel:{{ $assignment->student->parentUser->phone }}" class="mt-1 block text-sm font-semibold text-emerald-800 hover:underline">
                                                {{ $assignment->student->parentUser->phone }}
                                            </a>
                                        @else
                                            <p class="mt-1 text-sm font-semibold text-slate-950">-</p>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="grid gap-3">
                                <form method="POST" action="{{ route('chauffeur.logs.store') }}" class="rounded-3xl border border-emerald-200 bg-emerald-50 px-4 py-4">
                                    @csrf
                                    <input type="hidden" name="transport_assignment_id" value="{{ $assignment->id }}">
                                    <input type="hidden" name="status" value="{{ \App\Models\TransportLog::STATUS_BOARDED }}">
                                    <div class="space-y-3">
                                        <select name="route_stop_id" class="app-input">
                                            <option value="">Arrêt montée</option>
                                            @foreach($stops as $stop)
                                                <option value="{{ $stop->id }}" @selected($todayBoarded?->route_stop_id === $stop->id)>{{ $stop->stop_order }}. {{ $stop->name }}{{ $stop->scheduled_time ? ' - '.$stop->scheduled_time : '' }}</option>
                                            @endforeach
                                        </select>
                                        <input name="note" value="{{ $todayBoarded?->note }}" class="app-input" placeholder="Note montée">
                                        <x-ui.button type="submit" variant="primary" class="w-full justify-center">
                                            {{ $todayBoarded ? 'Mettre a jour montée' : 'Confirmer montée' }}
                                        </x-ui.button>
                                    </div>
                                </form>

                                <form method="POST" action="{{ route('chauffeur.logs.store') }}" class="rounded-3xl border border-sky-200 bg-sky-50 px-4 py-4">
                                    @csrf
                                    <input type="hidden" name="transport_assignment_id" value="{{ $assignment->id }}">
                                    <input type="hidden" name="status" value="{{ \App\Models\TransportLog::STATUS_DROPPED }}">
                                    <div class="space-y-3">
                                        <select name="route_stop_id" class="app-input">
                                            <option value="">Arrêt descente</option>
                                            @foreach($stops as $stop)
                                                <option value="{{ $stop->id }}" @selected($todayDropped?->route_stop_id === $stop->id)>{{ $stop->stop_order }}. {{ $stop->name }}{{ $stop->scheduled_time ? ' - '.$stop->scheduled_time : '' }}</option>
                                            @endforeach
                                        </select>
                                        <input name="note" value="{{ $todayDropped?->note }}" class="app-input" placeholder="Note descente">
                                        <x-ui.button type="submit" variant="secondary" class="w-full justify-center">
                                            {{ $todayDropped ? 'Mettre a jour descente' : 'Confirmer descente' }}
                                        </x-ui.button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="px-6 py-12 text-center text-sm text-slate-500">
                        Aucun Élève affecte a vos véhicules pour ces filtres.
                    </div>
                @endforelse
            </div>
        </div>

        <aside class="space-y-5">
            <div class="rounded-[30px] border border-slate-200 bg-white px-5 py-5 shadow-sm">
                <h2 class="text-xl font-semibold text-slate-950">Mes véhicules</h2>
                <p class="mt-1 text-sm text-slate-500">Véhicules assignes a votre compte.</p>
                <div class="mt-4 space-y-3">
                    @forelse($vehicles as $vehicle)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-slate-950">{{ $vehicle->name ?: 'Vehicule' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $vehicle->registration_number ?: $vehicle->plate_number ?: '-' }}</p>
                                </div>
                                <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-slate-600 ring-1 ring-slate-200">{{ $vehicle->capacity ?: '-' }} places</span>
                            </div>
                            @if($vehicle->assistant_name)
                                <p class="mt-2 text-xs text-slate-500">Assistant : {{ $vehicle->assistant_name }}</p>
                            @endif
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-sm text-slate-500">
                            Aucun vehicule n'est encore lié a votre compte.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="rounded-[30px] border border-slate-200 bg-white px-5 py-5 shadow-sm">
                <h2 class="text-xl font-semibold text-slate-950">Journal du jour</h2>
                <p class="mt-1 text-sm text-slate-500">Dernières opérations enregistrées.</p>
                <div class="mt-4 space-y-3">
                    @forelse($todayLogs->take(14) as $log)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-slate-950">{{ $log->student?->full_name ?? '-' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $log->logged_at?->format('H:i') }}
                                        <span class="mx-1 text-slate-300">|</span>
                                        {{ $log->route?->route_name ?? '-' }}
                                    </p>
                                </div>
                                <x-ui.badge :variant="$log->status === \App\Models\TransportLog::STATUS_BOARDED ? 'success' : 'info'">
                                    {{ $log->status === \App\Models\TransportLog::STATUS_BOARDED ? 'Montee' : 'Descente' }}
                                </x-ui.badge>
                            </div>
                            @if($log->stop?->name || $log->note)
                                <p class="mt-2 text-xs text-slate-500">{{ $log->stop?->name }}{{ $log->note ? ' - '.$log->note : '' }}</p>
                            @endif
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-sm text-slate-500">
                            Aucun pointage aujourd'hui.
                        </div>
                    @endforelse
                </div>
            </div>
        </aside>
    </section>
</x-chauffeur-layout>
