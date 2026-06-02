<x-dynamic-component :component="$layoutComponent" title="Paiements Événements">
    <section class="overflow-hidden rounded-[32px] border border-sky-100 bg-[radial-gradient(circle_at_top_right,_rgba(14,165,233,0.18),_transparent_34%),radial-gradient(circle_at_bottom_left,_rgba(16,185,129,0.14),_transparent_32%),linear-gradient(135deg,#ffffff,#f8fbff_55%,#eefdf8)] px-6 py-6 text-slate-950 shadow-xl shadow-slate-200/70 md:px-8">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="max-w-2xl">
                <div class="inline-flex rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-sky-700">
                    Événements scolaires
                </div>
                <h1 class="mt-4 text-3xl font-semibold tracking-tight md:text-4xl">Paiements Événements</h1>
                <p class="mt-3 text-sm leading-6 text-slate-600">
                    Fêtes, sorties, voyages, photos ou activités spéciales avec suivi des paiements et reçus.
                </p>
            </div>

            <x-ui.button :href="route($routePrefix . '.create')" variant="primary">
                Nouvel Événement
            </x-ui.button>
        </div>
    </section>

    <section class="mt-6 rounded-[28px] border border-slate-200 bg-white px-5 py-5 shadow-sm">
        <form method="GET" action="{{ route($routePrefix . '.index') }}" class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_220px_auto] lg:items-end">
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-800">Recherche</label>
                <input name="q" value="{{ $q }}" class="app-input" placeholder="Nom de l Événement">
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-800">Statut</label>
                <select name="status" class="app-input">
                    <option value="">Tous</option>
                    <option value="active" @selected($status === 'active')>Actifs</option>
                    <option value="closed" @selected($status === 'closed')>Clotures</option>
                </select>
            </div>
            <div class="flex gap-2">
                <x-ui.button type="submit" variant="primary">Filtrer</x-ui.button>
                <x-ui.button :href="route($routePrefix . '.index')" variant="secondary">Reset</x-ui.button>
            </div>
        </form>
    </section>

    <section class="mt-6 grid gap-5 lg:grid-cols-2">
        @forelse($events as $event)
            @php
                $expected = (float) ($event->expected_total ?? 0);
                $paid = (float) ($event->paid_total ?? 0);
                $remaining = max(0, $expected - $paid);
                $progress = $expected > 0 ? min(100, round(($paid / $expected) * 100)) : 0;
            @endphp
            <article class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-6 py-5">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <x-ui.badge :variant="$event->status === 'active' ? 'success' : 'warning'">{{ $event->status }}</x-ui.badge>
                            <h2 class="mt-3 truncate text-xl font-semibold tracking-tight text-slate-950">{{ $event->title }}</h2>
                            <p class="mt-1 text-sm text-slate-500">
                                {{ optional($event->event_date)->format('d/m/Y') ?: 'Date non définie' }} | {{ $event->targets_count }} Élève(s)
                            </p>
                        </div>
                        <p class="shrink-0 text-right text-sm font-semibold text-slate-900">{{ number_format((float) $event->amount_per_student, 2) }} MAD</p>
                    </div>
                </div>

                <div class="space-y-4 px-6 py-5">
                    <div class="grid gap-3 sm:grid-cols-3">
                        <div class="rounded-2xl bg-sky-50 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-sky-700">Attendu</p>
                            <p class="mt-2 text-lg font-bold text-slate-950">{{ number_format($expected, 2) }}</p>
                        </div>
                        <div class="rounded-2xl bg-emerald-50 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-emerald-700">Encaisse</p>
                            <p class="mt-2 text-lg font-bold text-slate-950">{{ number_format($paid, 2) }}</p>
                        </div>
                        <div class="rounded-2xl bg-amber-50 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-amber-700">Reste</p>
                            <p class="mt-2 text-lg font-bold text-slate-950">{{ number_format($remaining, 2) }}</p>
                        </div>
                    </div>

                    <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-emerald-500" style="width: {{ $progress }}%"></div>
                    </div>

                    <div class="flex flex-wrap justify-end gap-2">
                        <x-ui.button :href="route($routePrefix . '.show', $event)" variant="primary" size="sm">Ouvrir</x-ui.button>
                        <x-ui.button :href="route($routePrefix . '.edit', $event)" variant="secondary" size="sm">Modifier</x-ui.button>
                        <form method="POST" action="{{ route($routePrefix . '.destroy', $event) }}" onsubmit="return confirm('Supprimer cet Événement ?')">
                            @csrf
                            @method('DELETE')
                            <x-ui.button type="submit" variant="danger" size="sm">Supprimer</x-ui.button>
                        </form>
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-[28px] border border-slate-200 bg-white px-6 py-12 text-center text-sm text-slate-500 lg:col-span-2">
                Aucun Événement payant pour le moment.
            </div>
        @endforelse
    </section>

    <div class="mt-6">
        {{ $events->links() }}
    </div>
</x-dynamic-component>
