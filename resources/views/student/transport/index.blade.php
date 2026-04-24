<x-student-layout title="Mon transport" subtitle="Consultez votre affectation transport et les derniers pointages montee/descente.">
    <section class="grid gap-4 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)]">
        <x-ui.card title="Affectation active" subtitle="Route, vehicule, chauffeur et arrets associes a votre compte.">
            @php($assignment = $student->transportAssignment)
            @if($assignment && $assignment->is_active)
                <div class="space-y-3">
                    <div>
                        <p class="text-sm text-slate-500">Route</p>
                        <p class="text-base font-semibold text-slate-900">{{ $assignment->route?->route_name ?? '-' }}</p>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <p class="text-sm text-slate-500">Vehicule</p>
                            <p class="font-semibold text-slate-900">
                                {{ $assignment->vehicle?->name ?: ($assignment->vehicle?->registration_number ?? '-') }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500">Chauffeur</p>
                            <p class="font-semibold text-slate-900">{{ $assignment->vehicle?->driver?->name ?? '-' }}</p>
                        </div>
                    </div>
                    @if($assignment->pickup_point)
                        <div>
                            <p class="text-sm text-slate-500">Point montee</p>
                            <p class="font-semibold text-slate-900">{{ $assignment->pickup_point }}</p>
                        </div>
                    @endif
                    @if($assignment->route?->stops?->isNotEmpty())
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
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
                </div>
            @else
                <div class="student-empty">Aucune affectation transport active pour le moment.</div>
            @endif
        </x-ui.card>

        <x-ui.card title="Derniers pointages" subtitle="Historique recent des passages transport.">
            <div class="space-y-2">
                @forelse($logs as $log)
                    <article class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-semibold text-slate-900">{{ $log->route?->route_name ?? '-' }}</p>
                            <x-ui.badge :variant="$log->status === \App\Models\TransportLog::STATUS_BOARDED ? 'success' : 'info'">
                                {{ $log->status === \App\Models\TransportLog::STATUS_BOARDED ? 'Montee' : 'Descente' }}
                            </x-ui.badge>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">{{ $log->logged_at?->format('d/m/Y H:i') }}</p>
                        @if($log->note)
                            <p class="mt-1 text-sm text-slate-600">{{ $log->note }}</p>
                        @endif
                    </article>
                @empty
                    <div class="student-empty">Aucun pointage transport enregistre.</div>
                @endforelse
            </div>
        </x-ui.card>
    </section>
</x-student-layout>
