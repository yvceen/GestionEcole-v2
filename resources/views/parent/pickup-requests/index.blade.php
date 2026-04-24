<x-parent-layout title="Demandes de recuperation" subtitle="Demandez une sortie / recuperation de votre enfant et suivez la decision de la vie scolaire.">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-semibold text-slate-950">Mes demandes</h2>
            <p class="mt-1 text-sm text-slate-500">Les demandes sont visibles par le responsable scolaire de l'etablissement.</p>
        </div>
        <x-ui.button :href="route('parent.pickup-requests.create')" variant="primary">
            Nouvelle demande
        </x-ui.button>
    </div>

    <section class="mt-6 grid gap-4">
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
            <article class="student-panel">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div>
                        <p class="student-eyebrow">Recuperation</p>
                        <h3 class="mt-2 text-lg font-semibold text-slate-950">{{ $request->student?->full_name ?? '-' }}</h3>
                        <p class="mt-2 text-sm text-slate-500">
                            {{ $request->student?->classroom?->name ?? '-' }}
                            <span class="mx-2 text-slate-300">|</span>
                            {{ $request->requested_pickup_at?->format('d/m/Y H:i') }}
                        </p>
                    </div>
                    <x-ui.badge :variant="$variant">{{ $label }}</x-ui.badge>
                </div>

                @if($request->reason)
                    <p class="mt-4 text-sm text-slate-600">{{ $request->reason }}</p>
                @endif

                @if($request->decision_note || $request->reviewedBy)
                    <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                        @if($request->reviewedBy)
                            <p>Traite par : <span class="font-semibold text-slate-950">{{ $request->reviewedBy->name }}</span></p>
                        @endif
                        @if($request->decision_note)
                            <p class="mt-1">{{ $request->decision_note }}</p>
                        @endif
                    </div>
                @endif
            </article>
        @empty
            <div class="student-empty">Aucune demande de recuperation pour le moment.</div>
        @endforelse
    </section>

    <div class="mt-5">{{ $requests->links() }}</div>
</x-parent-layout>
