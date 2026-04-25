<x-admin-layout title="Documents">
    <x-ui.page-header
        title="Bibliotheque documentaire"
        subtitle="Centralisez les fichiers administratifs, pedagogiques et de communication en gardant le ciblage par ecole, classe ou role."
    />

    @if(session('success'))
        <x-ui.alert variant="success">{{ session('success') }}</x-ui.alert>
    @endif

    @if($errors->any())
        <x-ui.alert variant="error">
            <ul class="space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-ui.alert>
    @endif

    <section class="grid gap-6 xl:grid-cols-[minmax(0,1.45fr)_380px]">
        <div class="space-y-6">
            <x-ui.card title="Recherche et filtres" subtitle="Retrouvez vite un document par titre, categorie ou audience.">
                <form method="GET" class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_220px_220px_auto]">
                    <input name="q" value="{{ $q ?? '' }}" placeholder="Titre ou resume..." class="app-input">

                    <select name="category" class="app-input">
                        <option value="all">Toutes les categories</option>
                        @foreach($categories as $value)
                            <option value="{{ $value }}" @selected(($category ?? 'all') === $value)>{{ ucfirst($value) }}</option>
                        @endforeach
                    </select>

                    <select name="audience" class="app-input">
                        <option value="all">Toutes les audiences</option>
                        @foreach($audiences as $value)
                            <option value="{{ $value }}" @selected(($audience ?? 'all') === $value)>{{ ucfirst($value) }}</option>
                        @endforeach
                    </select>

                    <div class="flex items-center gap-3">
                        <x-ui.button type="submit" variant="primary">Filtrer</x-ui.button>
                        <x-ui.button :href="route('admin.documents.library.index')" variant="ghost">Reinitialiser</x-ui.button>
                    </div>
                </form>
            </x-ui.card>

            <div class="grid gap-4 lg:grid-cols-2">
                @forelse($documents as $document)
                    <x-ui.card :title="$document->title" :subtitle="optional($document->published_at)->format('d/m/Y') ?: 'Non publie'">
                        <div class="space-y-3 text-sm text-slate-600">
                            <div class="flex flex-wrap items-center gap-2">
                                <x-ui.badge variant="info">{{ ucfirst($document->category) }}</x-ui.badge>
                                <x-ui.badge :variant="$document->is_active ? 'success' : 'warning'">
                                    {{ $document->is_active ? 'Actif' : 'Masque' }}
                                </x-ui.badge>
                                <x-ui.badge variant="warning">
                                    {{ $document->audience_scope === 'school' ? 'Toute l ecole' : ($document->audience_scope === 'classroom' ? ($document->classroom?->name ?? 'Classe') : ('Role: '.$document->role)) }}
                                </x-ui.badge>
                            </div>

                            @if($document->summary)
                                <p>{{ $document->summary }}</p>
                            @endif

                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-500">
                                Fichier :
                                <a href="{{ $document->file_url }}" target="_blank" rel="noopener" class="font-semibold text-sky-700 hover:underline">
                                    Ouvrir le document
                                </a>
                                @if($document->mime_type)
                                    <span class="mx-1">|</span>{{ $document->mime_type }}
                                @endif
                            </div>

                            <details class="rounded-2xl border border-slate-200 px-4 py-3">
                                <summary class="cursor-pointer text-sm font-semibold text-slate-900">Modifier</summary>
                                <form method="POST" action="{{ route('admin.documents.library.update', $document) }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                                    @csrf
                                    @method('PUT')
                                    @include('admin.documents.library.partials.form', ['documentModel' => $document])
                                    <x-ui.button type="submit" variant="primary">Mettre a jour</x-ui.button>
                                </form>
                                <form method="POST" action="{{ route('admin.documents.library.destroy', $document) }}" class="mt-3" onsubmit="return confirm('Supprimer ce document ?')">
                                    @csrf
                                    @method('DELETE')
                                    <x-ui.button type="submit" variant="danger">Supprimer</x-ui.button>
                                </form>
                            </details>
                        </div>
                    </x-ui.card>
                @empty
                    <x-ui.card title="Aucun document" subtitle="Ajoutez un premier document pour demarrer la diffusion.">
                        <p class="text-sm text-slate-500">Les familles et eleves verront ici les fichiers reels mis a disposition selon leur audience.</p>
                    </x-ui.card>
                @endforelse
            </div>

            <div>{{ $documents->links() }}</div>
        </div>

        <x-ui.card title="Ajouter un document" subtitle="Ajoutez un document a partager avec les utilisateurs concernes. Formats acceptes : PDF, image ou document bureautique.">
            <form method="POST" action="{{ route('admin.documents.library.store') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                @include('admin.documents.library.partials.form', ['documentModel' => null])
                <x-ui.button type="submit" variant="primary">Ajouter le document</x-ui.button>
            </form>
        </x-ui.card>
    </section>
</x-admin-layout>
