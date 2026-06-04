<x-dynamic-component :component="$layoutComponent" title="Autorisations numériques" subtitle="Créez, validez et suivez les accords parentaux sans papier.">
    <section class="rounded-[28px] border border-sky-100 bg-gradient-to-r from-sky-50 via-white to-emerald-50 p-6 shadow-sm">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-sky-700">Suivi des accords</p>
                <h1 class="mt-2 text-2xl font-bold text-slate-950">{{ $isParent ? 'Autorisations de mes enfants' : 'Centre des autorisations' }}</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Sorties, droit à l’image, activités, transport et autorisations médicales avec une réponse traçable.</p>
            </div>
            @if($canManage)
                <x-ui.button :href="route($routePrefix.'.create')" variant="primary">Nouvelle autorisation</x-ui.button>
            @endif
        </div>
        <div class="mt-6 grid gap-3 sm:grid-cols-3">
            <div class="rounded-2xl border border-amber-100 bg-amber-50 p-4"><p class="text-xs font-bold uppercase tracking-wide text-amber-700">En attente</p><p class="mt-2 text-3xl font-bold text-slate-950">{{ $stats['pending'] }}</p></div>
            <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4"><p class="text-xs font-bold uppercase tracking-wide text-emerald-700">Acceptées</p><p class="mt-2 text-3xl font-bold text-slate-950">{{ $stats['approved'] }}</p></div>
            <div class="rounded-2xl border border-rose-100 bg-rose-50 p-4"><p class="text-xs font-bold uppercase tracking-wide text-rose-700">Refusées</p><p class="mt-2 text-3xl font-bold text-slate-950">{{ $stats['declined'] }}</p></div>
        </div>
    </section>

    <x-ui.card title="Rechercher" subtitle="Filtrez les demandes par titre ou statut.">
        <form method="GET" class="grid gap-3 md:grid-cols-[minmax(0,1fr)_220px_auto_auto]">
            <input name="q" value="{{ $q }}" class="app-input" placeholder="Titre de l’autorisation...">
            <select name="status" class="app-input"><option value="">Tous les statuts</option><option value="published" @selected($status === 'published')>Ouvertes</option><option value="closed" @selected($status === 'closed')>Clôturées</option></select>
            <x-ui.button type="submit" variant="primary">Filtrer</x-ui.button>
            <x-ui.button :href="route($routePrefix.'.index')" variant="secondary">Réinitialiser</x-ui.button>
        </form>
    </x-ui.card>

    <section class="grid gap-4 xl:grid-cols-2">
        @forelse($authorizations as $authorization)
            @php($total = max(1, (int) $authorization->recipients_count))
            <a href="{{ route($routePrefix.'.show', $authorization) }}" class="block rounded-[26px] border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-sky-200 hover:shadow-md">
                <div class="flex items-start justify-between gap-4">
                    <div><p class="text-xs font-bold uppercase tracking-[0.16em] text-sky-700">{{ \App\Models\DigitalAuthorization::categories()[$authorization->category] ?? 'Autorisation' }}</p><h2 class="mt-2 text-xl font-bold text-slate-950">{{ $authorization->title }}</h2></div>
                    <x-ui.badge :variant="$authorization->status === 'closed' ? 'info' : 'success'">{{ $authorization->status === 'closed' ? 'Clôturée' : 'Ouverte' }}</x-ui.badge>
                </div>
                <p class="mt-3 line-clamp-2 text-sm leading-6 text-slate-600">{{ $authorization->description }}</p>
                <div class="mt-5 grid grid-cols-3 gap-2 text-center text-sm">
                    <div class="rounded-xl bg-amber-50 p-3"><strong class="block text-lg text-amber-800">{{ $authorization->pending_count }}</strong><span class="text-amber-700">Attente</span></div>
                    <div class="rounded-xl bg-emerald-50 p-3"><strong class="block text-lg text-emerald-800">{{ $authorization->approved_count }}</strong><span class="text-emerald-700">Oui</span></div>
                    <div class="rounded-xl bg-rose-50 p-3"><strong class="block text-lg text-rose-800">{{ $authorization->declined_count }}</strong><span class="text-rose-700">Non</span></div>
                </div>
                <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-emerald-500" style="width: {{ round(($authorization->approved_count / $total) * 100) }}%"></div></div>
                <p class="mt-3 text-xs text-slate-500">{{ $authorization->due_at ? 'Réponse avant le '.$authorization->due_at->format('d/m/Y H:i') : 'Aucune date limite' }}</p>
            </a>
        @empty
            <div class="rounded-[26px] border border-dashed border-slate-300 bg-white px-6 py-16 text-center text-sm text-slate-500 xl:col-span-2">Aucune autorisation pour le moment.</div>
        @endforelse
    </section>
    <div>{{ $authorizations->links() }}</div>
</x-dynamic-component>
