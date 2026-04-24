<x-school-life-layout title="Demandes de recuperation" subtitle="Traitez les demandes parent pour recuperer un enfant pendant la journee scolaire.">
    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="app-stat-card">
            <p class="app-stat-label">En attente</p>
            <p class="app-stat-value text-amber-700">{{ $stats['pending'] }}</p>
            <p class="app-stat-meta">Demandes a traiter.</p>
        </article>
        <article class="app-stat-card">
            <p class="app-stat-label">Approuvees</p>
            <p class="app-stat-value text-emerald-700">{{ $stats['approved'] }}</p>
            <p class="app-stat-meta">En attente de recuperation effective.</p>
        </article>
        <article class="app-stat-card">
            <p class="app-stat-label">Traitees</p>
            <p class="app-stat-value text-sky-700">{{ $stats['completed'] }}</p>
            <p class="app-stat-meta">Sorties finalisees.</p>
        </article>
        <article class="app-stat-card">
            <p class="app-stat-label">Rejetees</p>
            <p class="app-stat-value text-rose-700">{{ $stats['rejected'] }}</p>
            <p class="app-stat-meta">Demandes refusees.</p>
        </article>
    </section>

    <x-ui.card title="Filtres" subtitle="Priorite aux demandes en attente.">
        <form method="GET" class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="app-field flex-1">
                <label class="app-label" for="status">Statut</label>
                <select id="status" name="status" class="app-input">
                    <option value="">Tous les statuts</option>
                    @foreach(\App\Models\PickupRequest::statuses() as $requestStatus)
                        <option value="{{ $requestStatus }}" @selected($status === $requestStatus)>
                            {{ match($requestStatus) {
                                'approved' => 'Approuvee',
                                'rejected' => 'Rejetee',
                                'completed' => 'Traitee',
                                default => 'En attente',
                            } }}
                        </option>
                    @endforeach
                </select>
            </div>
            <x-ui.button type="submit" variant="primary">Filtrer</x-ui.button>
            <x-ui.button :href="route('school-life.pickup-requests.index')" variant="secondary">Reinitialiser</x-ui.button>
        </form>
    </x-ui.card>

    <section class="grid gap-4">
        @forelse($requests as $request)
            @php
                $variant = match ($request->status) {
                    'approved' => 'success',
                    'rejected' => 'danger',
                    'completed' => 'info',
                    default => 'warning',
                };
                $label = match ($request->status) {
                    'approved' => 'Approuvee',
                    'rejected' => 'Rejetee',
                    'completed' => 'Traitee',
                    default => 'En attente',
                };
            @endphp
            <article class="app-card px-5 py-5">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-lg font-semibold text-slate-950">{{ $request->student?->full_name ?? '-' }}</h3>
                            <x-ui.badge :variant="$variant">{{ $label }}</x-ui.badge>
                        </div>
                        <p class="mt-2 text-sm text-slate-500">
                            {{ $request->student?->classroom?->name ?? '-' }}
                            <span class="mx-2 text-slate-300">|</span>
                            {{ $request->requested_pickup_at?->format('d/m/Y H:i') }}
                        </p>
                        <p class="mt-2 text-sm text-slate-600">
                            Parent : <span class="font-semibold text-slate-950">{{ $request->parentUser?->name ?? '-' }}</span>
                            <span class="mx-2 text-slate-300">|</span>
                            {{ $request->parentUser?->phone ?? $request->parentUser?->email ?? '-' }}
                        </p>
                        @if($request->reason)
                            <p class="mt-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">{{ $request->reason }}</p>
                        @endif
                    </div>

                    <div class="w-full xl:w-[360px]">
                        @if(in_array($request->status, ['pending', 'approved'], true))
                            <div class="space-y-3">
                                @if($request->status === 'pending')
                                    <form method="POST" action="{{ route('school-life.pickup-requests.approve', $request) }}" class="space-y-2">
                                        @csrf
                                        <textarea name="decision_note" rows="2" class="app-input" placeholder="Note optionnelle...">{{ old('decision_note', $request->decision_note) }}</textarea>
                                        <button class="app-button-outline min-h-10 w-full rounded-xl px-3 py-2 text-xs">Approuver</button>
                                    </form>

                                    <form method="POST" action="{{ route('school-life.pickup-requests.reject', $request) }}">
                                        @csrf
                                        <button class="app-button-danger min-h-10 w-full rounded-xl px-3 py-2 text-xs">Rejeter</button>
                                    </form>
                                @endif

                                @if($request->status === 'approved')
                                    <form method="POST" action="{{ route('school-life.pickup-requests.complete', $request) }}">
                                        @csrf
                                        <button class="app-button-primary min-h-10 w-full rounded-xl px-3 py-2 text-xs">Marquer comme traitee</button>
                                    </form>
                                @endif
                            </div>
                        @else
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-600">
                                @if($request->reviewedBy)
                                    Traite par <span class="font-semibold text-slate-950">{{ $request->reviewedBy->name }}</span>
                                @endif
                                @if($request->decision_note)
                                    <p class="mt-2">{{ $request->decision_note }}</p>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <div class="student-empty">Aucune demande de recuperation.</div>
        @endforelse
    </section>

    <div class="mt-5">{{ $requests->links() }}</div>
</x-school-life-layout>
