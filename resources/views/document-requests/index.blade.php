<x-dynamic-component :component="$layoutComponent" title="Demandes de documents" subtitle="Suivez les certificats, attestations et documents demandés.">
    <section class="rounded-[28px] border border-sky-100 bg-gradient-to-r from-sky-50 via-white to-emerald-50 p-6 shadow-sm">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-sky-700">Service administratif</p>
                <h1 class="mt-2 text-2xl font-bold text-slate-950">{{ $isParent ? 'Mes demandes de documents' : 'Certificats et documents' }}</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">{{ $isParent ? 'Demandez une attestation ou un certificat et suivez sa préparation.' : 'Traitez les demandes, préparez les fichiers et confirmez leur remise.' }}</p>
            </div>
            @if($isParent)
                <x-ui.button :href="route($routePrefix.'.create')" variant="primary">Nouvelle demande</x-ui.button>
            @endif
        </div>
        <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-amber-100 bg-amber-50 p-4"><p class="text-xs font-bold uppercase tracking-wide text-amber-700">En attente</p><p class="mt-2 text-3xl font-bold text-slate-950">{{ $stats['pending'] }}</p></div>
            <div class="rounded-2xl border border-sky-100 bg-sky-50 p-4"><p class="text-xs font-bold uppercase tracking-wide text-sky-700">En préparation</p><p class="mt-2 text-3xl font-bold text-slate-950">{{ $stats['processing'] }}</p></div>
            <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4"><p class="text-xs font-bold uppercase tracking-wide text-emerald-700">Prêtes</p><p class="mt-2 text-3xl font-bold text-slate-950">{{ $stats['ready'] }}</p></div>
            <div class="rounded-2xl border border-violet-100 bg-violet-50 p-4"><p class="text-xs font-bold uppercase tracking-wide text-violet-700">Remises</p><p class="mt-2 text-3xl font-bold text-slate-950">{{ $stats['delivered'] }}</p></div>
        </div>
    </section>

    <x-ui.card title="Recherche et filtres" subtitle="Retrouvez rapidement une demande par élève, parent ou statut.">
        <form method="GET" class="grid gap-3 md:grid-cols-[minmax(0,1fr)_220px_auto_auto]">
            <input name="q" value="{{ $q }}" class="app-input" placeholder="Élève, parent ou document...">
            <select name="status" class="app-input">
                <option value="">Tous les statuts</option>
                @foreach(\App\Models\DocumentRequest::statuses() as $value => $label)
                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <x-ui.button type="submit" variant="primary">Filtrer</x-ui.button>
            <x-ui.button :href="route($routePrefix.'.index')" variant="secondary">Réinitialiser</x-ui.button>
        </form>
    </x-ui.card>

    <section class="grid gap-4">
        @forelse($requests as $item)
            @php
                $variant = match($item->status) {'ready', 'delivered' => 'success', 'rejected', 'cancelled' => 'danger', 'processing' => 'info', default => 'warning'};
            @endphp
            <a href="{{ route($routePrefix.'.show', $item) }}" class="grid gap-4 rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm transition hover:border-sky-200 hover:shadow-md lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)_minmax(0,.8fr)_auto] lg:items-center">
                <div>
                    <div class="flex flex-wrap items-center gap-2"><h2 class="text-lg font-bold text-slate-950">{{ $item->type_label }}</h2><x-ui.badge :variant="$variant">{{ \App\Models\DocumentRequest::statuses()[$item->status] ?? $item->status }}</x-ui.badge></div>
                    <p class="mt-1 text-sm text-slate-500">Demandée le {{ $item->created_at->format('d/m/Y à H:i') }}</p>
                </div>
                <div><p class="text-xs font-bold uppercase tracking-wide text-slate-400">Élève</p><p class="mt-1 font-semibold text-slate-800">{{ $item->student?->full_name }}</p><p class="text-xs text-slate-500">{{ $item->student?->classroom?->name }}</p></div>
                <div><p class="text-xs font-bold uppercase tracking-wide text-slate-400">Format</p><p class="mt-1 font-semibold text-slate-800">{{ strtoupper($item->language) }} · {{ $item->copies }} exemplaire(s)</p><p class="text-xs text-slate-500">{{ $item->delivery_method === 'digital' ? 'Envoi numérique' : 'Retrait à l’école' }}</p></div>
                <span class="text-sm font-bold text-sky-700">Voir le suivi</span>
            </a>
        @empty
            <div class="rounded-[26px] border border-dashed border-slate-300 bg-white px-6 py-16 text-center text-sm text-slate-500">Aucune demande ne correspond aux filtres.</div>
        @endforelse
    </section>
    <div>{{ $requests->links() }}</div>
</x-dynamic-component>
