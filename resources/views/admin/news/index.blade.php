@php
    $routePrefix = $routePrefix ?? 'admin.news';
    $layoutComponent = $layoutComponent ?? 'admin-layout';
    $canManage = $canManage ?? true;
@endphp

<x-dynamic-component :component="$layoutComponent" title="Actualites">
    <x-ui.page-header
        title="Actualites"
        subtitle="Pilotez les publications de l etablissement avec une vue plus lisible sur le contenu, la cible et la date de diffusion."
    >
        <x-slot name="actions">
            @if($canManage)
                <x-ui.button :href="route($routePrefix . '.create')" variant="primary">Nouvelle actualite</x-ui.button>
            @endif
        </x-slot>
    </x-ui.page-header>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="app-stat-card">
            <p class="app-stat-label">Total</p>
            <p class="app-stat-value">{{ $stats['total'] ?? 0 }}</p>
            <p class="app-stat-meta">Publications de l ecole active.</p>
        </article>
        <article class="app-stat-card">
            <p class="app-stat-label">Publiees</p>
            <p class="app-stat-value text-emerald-700">{{ $stats['published'] ?? 0 }}</p>
            <p class="app-stat-meta">Visibles pour les utilisateurs cibles.</p>
        </article>
        <article class="app-stat-card">
            <p class="app-stat-label">Brouillons</p>
            <p class="app-stat-value text-amber-700">{{ $stats['draft'] ?? 0 }}</p>
            <p class="app-stat-meta">Encore en preparation.</p>
        </article>
        <article class="app-stat-card">
            <p class="app-stat-label">Mises en avant</p>
            <p class="app-stat-value text-sky-700">{{ $stats['pinned'] ?? 0 }}</p>
            <p class="app-stat-meta">Epinglees en tete des listes.</p>
        </article>
    </section>

    <x-ui.card title="Recherche et filtres" subtitle="Filtrez rapidement par statut, audience ou mot-cle.">
        <form method="GET" class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_220px_220px_auto]">
            <input name="q" value="{{ $q ?? '' }}" placeholder="Titre, resume ou contenu..."
                   class="app-input">

            <select name="status" class="app-input">
                <option value="all" @selected(($status ?? 'all') === 'all')>Tous les statuts</option>
                <option value="draft" @selected(($status ?? '') === 'draft')>Brouillon</option>
                <option value="published" @selected(($status ?? '') === 'published')>Publie</option>
                <option value="archived" @selected(($status ?? '') === 'archived')>Archive</option>
            </select>

            <select name="scope" class="app-input">
                <option value="all" @selected(($scope ?? 'all') === 'all')>Toutes les audiences</option>
                <option value="school" @selected(($scope ?? '') === 'school')>Toute l ecole</option>
                <option value="classroom" @selected(($scope ?? '') === 'classroom')>Classe</option>
            </select>

            <div class="flex items-center gap-3">
                <x-ui.button type="submit" variant="primary">Filtrer</x-ui.button>
                <x-ui.button :href="route($routePrefix . '.index')" variant="ghost">Reinitialiser</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    <div class="grid gap-5 xl:grid-cols-2">
        @forelse($items as $item)
            @php
                $variant = match($item->status) {
                    'published' => 'success',
                    'archived' => 'danger',
                    default => 'warning',
                };
            @endphp
            <x-ui.card :title="$item->title" :subtitle="optional($item->date)->format('d/m/Y') ?: 'Date non definie'">
                <div class="flex flex-col gap-4">
                    @if($item->cover_url)
                        <img src="{{ $item->cover_url }}" alt="Couverture actualite" class="h-48 w-full rounded-2xl object-cover">
                    @endif

                    <div class="flex flex-wrap items-center gap-2">
                        <x-ui.badge :variant="$variant">{{ ucfirst($item->status ?? 'draft') }}</x-ui.badge>
                        <x-ui.badge :variant="($item->scope ?? 'school') === 'school' ? 'info' : 'warning'">
                            {{ ($item->scope ?? 'school') === 'school' ? 'Toute l ecole' : ($item->classroom?->name ?? 'Classe') }}
                        </x-ui.badge>
                        @if($item->is_pinned)
                            <x-ui.badge variant="info">Epinglee</x-ui.badge>
                        @endif
                        @if($item->source_type)
                            <x-ui.badge variant="info">{{ $item->source_type }}</x-ui.badge>
                        @endif
                    </div>

                    <p class="text-sm leading-6 text-slate-600">{{ $item->excerpt }}</p>

                    @if($canManage)
                        <div class="flex justify-end gap-2">
                            <x-ui.button :href="route($routePrefix . '.edit', $item)" variant="secondary" size="sm">Modifier</x-ui.button>
                            <form method="POST" action="{{ route($routePrefix . '.destroy', $item) }}" onsubmit="return confirm('Supprimer cette actualite ?')">
                                @csrf
                                @method('DELETE')
                                <x-ui.button type="submit" variant="danger" size="sm">Supprimer</x-ui.button>
                            </form>
                        </div>
                    @endif
                </div>
            </x-ui.card>
        @empty
            <x-ui.card title="Aucune actualite" subtitle="Aucune publication ne correspond aux filtres actuels.">
                <p class="text-sm text-slate-500">Commencez par creer une actualite ou ajustez les filtres de recherche.</p>
            </x-ui.card>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $items->links() }}
    </div>
</x-dynamic-component>
