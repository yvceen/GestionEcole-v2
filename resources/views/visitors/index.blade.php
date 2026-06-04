<x-dynamic-component :component="$layoutComponent" title="Gestion des visiteurs" subtitle="Accueil, présence en temps réel et historique des visites.">
    <section class="rounded-[28px] border border-sky-100 bg-gradient-to-r from-sky-50 via-white to-emerald-50 p-6 shadow-sm">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div><p class="text-xs font-bold uppercase tracking-[0.18em] text-sky-700">Accueil sécurisé</p><h1 class="mt-2 text-2xl font-bold text-slate-950">Registre des visiteurs</h1><p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Sachez immédiatement qui est présent, qui est attendu et qui a quitté l’établissement.</p></div>
            <x-ui.button :href="route($routePrefix.'.create')" variant="primary">Enregistrer un visiteur</x-ui.button>
        </div>
        <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4"><p class="text-xs font-bold uppercase tracking-wide text-emerald-700">Présents maintenant</p><p class="mt-2 text-3xl font-bold text-slate-950">{{ $stats['inside'] }}</p></div>
            <div class="rounded-2xl border border-sky-100 bg-sky-50 p-4"><p class="text-xs font-bold uppercase tracking-wide text-sky-700">Attendus aujourd’hui</p><p class="mt-2 text-3xl font-bold text-slate-950">{{ $stats['expected_today'] }}</p></div>
            <div class="rounded-2xl border border-violet-100 bg-violet-50 p-4"><p class="text-xs font-bold uppercase tracking-wide text-violet-700">Sorties aujourd’hui</p><p class="mt-2 text-3xl font-bold text-slate-950">{{ $stats['completed_today'] }}</p></div>
            <div class="rounded-2xl border border-amber-100 bg-amber-50 p-4"><p class="text-xs font-bold uppercase tracking-wide text-amber-700">Visites du jour</p><p class="mt-2 text-3xl font-bold text-slate-950">{{ $stats['total_today'] }}</p></div>
        </div>
    </section>

    <x-ui.card title="Recherche et filtres" subtitle="Recherchez par nom, téléphone, identité, badge, élève ou personne visitée.">
        <form method="GET" class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_200px_190px_auto_auto]">
            <input name="q" value="{{ $q }}" class="app-input" placeholder="Nom, téléphone, badge...">
            <select name="status" class="app-input"><option value="inside" @selected($status === 'inside')>Présents</option><option value="expected" @selected($status === 'expected')>Attendus</option><option value="completed" @selected($status === 'completed')>Sortis</option><option value="cancelled" @selected($status === 'cancelled')>Annulés</option><option value="all" @selected($status === 'all')>Tous</option></select>
            <input type="date" name="date" value="{{ $date }}" class="app-input">
            <x-ui.button type="submit" variant="primary">Filtrer</x-ui.button>
            <x-ui.button :href="route($routePrefix.'.index', ['status' => 'inside', 'date' => now()->toDateString()])" variant="secondary">Aujourd’hui</x-ui.button>
        </form>
    </x-ui.card>

    <section class="grid gap-4">
        @forelse($visits as $visit)
            @php
                $variant = match($visit->status) {'checked_in' => 'success', 'checked_out' => 'info', 'cancelled' => 'danger', default => 'warning'};
                $label = match($visit->status) {'checked_in' => 'Présent', 'checked_out' => 'Sorti', 'cancelled' => 'Annulé', default => 'Attendu'};
            @endphp
            <a href="{{ route($routePrefix.'.show', $visit) }}" class="grid gap-4 rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm transition hover:border-sky-200 hover:shadow-md md:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)_minmax(0,1fr)_auto] md:items-center">
                <div><div class="flex flex-wrap items-center gap-2"><h2 class="text-lg font-bold text-slate-950">{{ $visit->visitor_name }}</h2><x-ui.badge :variant="$variant">{{ $label }}</x-ui.badge></div><p class="mt-1 text-sm text-slate-500">{{ $visit->phone ?: 'Téléphone non renseigné' }} · Badge {{ $visit->badge_code }}</p></div>
                <div><p class="text-xs font-bold uppercase tracking-wide text-slate-400">Motif</p><p class="mt-1 font-semibold text-slate-800">{{ \App\Models\VisitorVisit::purposes()[$visit->purpose] ?? $visit->purpose }}</p></div>
                <div><p class="text-xs font-bold uppercase tracking-wide text-slate-400">Destination</p><p class="mt-1 font-semibold text-slate-800">{{ $visit->hostUser?->name ?? $visit->student?->full_name ?? 'Accueil' }}</p><p class="text-xs text-slate-500">{{ $visit->student?->classroom?->name }}</p></div>
                <div class="text-right"><p class="text-xs text-slate-500">Entrée</p><p class="font-bold text-slate-950">{{ $visit->checked_in_at?->format('H:i') ?? $visit->expected_at?->format('H:i') ?? '-' }}</p>@if($visit->checked_out_at)<p class="mt-1 text-xs text-slate-500">Sortie {{ $visit->checked_out_at->format('H:i') }}</p>@endif</div>
            </a>
        @empty
            <div class="rounded-[26px] border border-dashed border-slate-300 bg-white px-6 py-16 text-center text-sm text-slate-500">Aucune visite ne correspond aux filtres.</div>
        @endforelse
    </section>
    <div>{{ $visits->links() }}</div>
</x-dynamic-component>
