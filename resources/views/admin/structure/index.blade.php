<x-admin-layout title="Structure scolaire">
    @php
        $totalLevels = $levels->count();
        $totalClasses = $levels->sum(fn($l) => $l->classrooms->count());
        $totalStudents = $levels->sum(fn($l) => $l->classrooms->sum('students_count'));
    @endphp

    @if($errors->any())
        <x-ui.alert variant="error">
            <div class="font-semibold">Certaines actions n'ont pas pu être appliquées.</div>
            <ul class="mt-2 list-disc pl-5">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </x-ui.alert>
    @endif

    <section class="grid gap-6 xl:grid-cols-[minmax(0,1.65fr)_minmax(320px,0.9fr)]">
        <div class="app-card px-6 py-6 md:px-7">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                <div class="max-w-2xl">
                    <p class="app-overline">Organisation pédagogique</p>
                    <h2 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">Niveaux, classes et effectifs</h2>
                    <p class="mt-3 text-sm leading-6 text-slate-500">
                        Centralisez la structure scolaire, créez rapidement vos niveaux et classes, puis ouvrez chaque classe pour consulter ses élèves.
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <x-ui.button :href="'#forms'" variant="primary">Ajouter un niveau ou une classe</x-ui.button>
                </div>
            </div>

            <div class="mt-6 grid gap-3 sm:grid-cols-3">
                <div class="app-stat-card">
                    <div class="app-stat-label">Niveaux</div>
                    <div class="app-stat-value">{{ $totalLevels }}</div>
                    <div class="app-stat-meta">Cycles ou groupes pédagogiques configurés.</div>
                </div>
                <div class="app-stat-card">
                    <div class="app-stat-label">Classes</div>
                    <div class="app-stat-value">{{ $totalClasses }}</div>
                    <div class="app-stat-meta">Classes actives rattachées aux niveaux.</div>
                </div>
                <div class="app-stat-card">
                    <div class="app-stat-label">Élèves</div>
                    <div class="app-stat-value">{{ $totalStudents }}</div>
                    <div class="app-stat-meta">Total d'élèves comptabilisés dans la structure.</div>
                </div>
            </div>
        </div>

        <div class="app-card px-6 py-6">
            <div class="space-y-5">
                <div>
                    <p class="app-overline">Recherche</p>
                    <h2 class="mt-2 text-lg font-semibold text-slate-900">Retrouver un niveau ou une classe</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Filtrez instantanément la liste pour accéder plus vite à une section précise.
                    </p>
                </div>

                <div class="relative">
                    <input id="searchBox" type="text"
                           placeholder="Rechercher (niveau, classe, section)..."
                           class="app-input rounded-2xl pl-11 pr-4" />
                    <div class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-slate-400" aria-hidden="true">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <circle cx="11" cy="11" r="6"></circle>
                            <path d="M16 16l4.5 4.5" stroke-linecap="round"></path>
                        </svg>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                    <div class="text-sm font-semibold text-slate-900">Bon à savoir</div>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Pour “CP B”, le slug devient automatiquement “CP-B”. L'ordre des classes est généré automatiquement à la création.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section id="forms" class="grid gap-6 xl:grid-cols-2">
        <div class="app-card px-6 py-6 md:px-7">
            <div class="border-b border-slate-200 pb-5">
                <p class="app-overline">Création</p>
                <h2 class="mt-2 text-lg font-semibold text-slate-900">Ajouter un niveau</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">Créez un niveau avec un code court et un nom lisible pour l'équipe pédagogique.</p>
            </div>

            <form method="POST" action="{{ route('admin.structure.levels.store') }}" class="mt-6 space-y-5">
                @csrf

                <div class="grid gap-5 md:grid-cols-2">
                    <div class="app-field">
                        <label class="app-label">Code</label>
                        <input name="code" value="{{ old('code') }}" required class="app-input" placeholder="PRI">
                        <p class="app-hint">Ex. : MAT, PRI, COL ou LYC.</p>
                    </div>

                    <div class="app-field">
                        <label class="app-label">Nom</label>
                        <input name="name" value="{{ old('name') }}" required class="app-input" placeholder="Primaire">
                        <p class="app-hint">Nom affiché dans la structure et les formulaires.</p>
                    </div>
                </div>

                <div class="flex justify-end">
                    <x-ui.button type="submit" variant="primary">Ajouter un niveau</x-ui.button>
                </div>
            </form>
        </div>

        <div class="app-card px-6 py-6 md:px-7">
            <div class="border-b border-slate-200 pb-5">
                <p class="app-overline">Création</p>
                <h2 class="mt-2 text-lg font-semibold text-slate-900">Ajouter une classe</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">Associez une classe à un niveau existant et définissez, si besoin, une section personnalisée.</p>
            </div>

            <form method="POST" action="{{ route('admin.structure.classrooms.store') }}" class="mt-6 space-y-5">
                @csrf

                <div class="app-field">
                    <label class="app-label">Niveau</label>
                    <select name="level_id" required class="app-input">
                        <option value="">Choisir</option>
                        @foreach($levels as $lvl)
                            <option value="{{ $lvl->id }}" @selected((int) old('level_id') === (int) $lvl->id)>
                                {{ $lvl->name }} ({{ $lvl->code }})
                            </option>
                        @endforeach
                    </select>
                    @if($levels->count() === 0)
                        <p class="app-error">Aucun niveau disponible. Ajoutez d'abord un niveau.</p>
                    @else
                        <p class="app-hint">Sélectionnez d'abord le niveau auquel rattacher la classe.</p>
                    @endif
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <div class="app-field">
                        <label class="app-label">Nom affiché</label>
                        <input name="name" value="{{ old('name') }}" required placeholder="CP A" class="app-input">
                        <p class="app-hint">Ex. : CP A, CE1 B, 6e A.</p>
                    </div>

                    <div class="app-field">
                        <label class="app-label">Section (slug)</label>
                        <input name="section" value="{{ old('section') }}" placeholder="CP-A" class="app-input">
                        <p class="app-hint">Optionnel. Générée automatiquement sinon.</p>
                    </div>
                </div>

                <div class="flex justify-end">
                    <x-ui.button type="submit" variant="primary" :disabled="$levels->count() === 0">Ajouter une classe</x-ui.button>
                </div>
            </form>
        </div>
    </section>

    <section class="space-y-5">
        <div class="app-card overflow-hidden px-6 py-6 md:px-7">
            <div class="grid gap-6 xl:grid-cols-[minmax(0,1.5fr)_minmax(280px,0.7fr)] xl:items-center">
                <div class="min-w-0">
                    <div class="flex items-center gap-3">
                        <div class="grid h-12 w-12 place-items-center rounded-2xl bg-sky-50 text-sky-700 ring-1 ring-sky-100">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h10"/>
                            </svg>
                        </div>
                        <div>
                            <p class="app-overline">Organisation</p>
                            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-950">Niveaux et classes</h2>
                        </div>
                    </div>

                    <p class="mt-4 max-w-3xl text-sm leading-6 text-slate-500">
                        Parcourez la structure de l'établissement niveau par niveau, consultez les classes associées et accédez rapidement aux actions de modification ou de suppression.
                    </p>

                    <div class="mt-5 flex flex-wrap gap-2">
                        <span class="inline-flex items-center rounded-full border border-sky-100 bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700">
                            Vue hiérarchique
                        </span>
                        <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600">
                            Dépliez un niveau pour voir ses classes
                        </span>
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/90 px-4 py-4">
                        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.16em] text-slate-500">Niveaux actifs</div>
                        <div class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">{{ $totalLevels }}</div>
                        <div class="mt-1 text-xs text-slate-500">Structure principale configurée.</div>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm">
                        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.16em] text-slate-500">Classes recensées</div>
                        <div class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">{{ $totalClasses }}</div>
                        <div class="mt-1 text-xs text-slate-500">Répartition visible par niveau.</div>
                    </div>
                </div>
            </div>
        </div>

        @if($levels->count() === 0)
            <x-ui.empty-state
                title="Aucun niveau pour le moment"
                description="Commencez par créer un niveau puis ajoutez vos classes pour structurer l'établissement."
            />
        @else
            <div id="levelsWrap" class="space-y-4">
                @foreach($levels as $lvl)
                    @php
                        $lvlStudents = $lvl->classrooms->sum('students_count');
                    @endphp

                    <details class="level-item app-card overflow-hidden" data-search="{{ strtolower($lvl->name.' '.$lvl->code) }}">
                        <summary class="cursor-pointer list-none px-6 py-5">
                            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-3">
                                        <h3 class="text-lg font-semibold text-slate-950">{{ strtoupper($lvl->name) }}</h3>
                                        <x-ui.badge variant="info">{{ strtoupper($lvl->code) }}</x-ui.badge>
                                    </div>

                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <x-ui.badge variant="info">{{ $lvl->classrooms->count() }} classe(s)</x-ui.badge>
                                        <x-ui.badge variant="info">{{ $lvlStudents }} élève(s)</x-ui.badge>
                                        <x-ui.badge variant="warning">Ordre : {{ $lvl->sort_order ?? '-' }}</x-ui.badge>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-2">
                                    <details class="relative">
                                        <summary class="list-none cursor-pointer app-button-secondary min-h-10 rounded-full px-4 py-2 text-xs">
                                            Modifier le niveau
                                        </summary>
                                        <div class="absolute right-0 z-20 mt-3 w-[22rem] rounded-2xl border border-slate-200 bg-white p-4 shadow-[0_28px_70px_-40px_rgba(15,23,42,0.34)]">
                                            <form method="POST" action="{{ route('admin.structure.levels.update', $lvl) }}" class="space-y-4">
                                                @csrf
                                                @method('PUT')

                                                <div class="grid gap-4 sm:grid-cols-2">
                                                    <div class="app-field">
                                                        <label class="app-label">Code</label>
                                                        <input name="code" value="{{ $lvl->code }}" class="app-input">
                                                    </div>
                                                    <div class="app-field">
                                                        <label class="app-label">Nom</label>
                                                        <input name="name" value="{{ $lvl->name }}" class="app-input">
                                                    </div>
                                                </div>

                                                <div class="flex justify-end">
                                                    <x-ui.button type="submit" variant="primary" size="sm">Enregistrer</x-ui.button>
                                                </div>
                                            </form>
                                        </div>
                                    </details>

                                    <form method="POST" action="{{ route('admin.structure.levels.destroy', $lvl) }}"
                                          onsubmit="return confirm('Supprimer ce niveau ? (il doit être vide)')">
                                        @csrf
                                        @method('DELETE')
                                        <x-ui.button type="submit" variant="danger" size="sm">Supprimer</x-ui.button>
                                    </form>
                                </div>
                            </div>
                        </summary>

                        <div class="border-t border-slate-200 px-6 pb-6 pt-5">
                            @if($lvl->classrooms->count() === 0)
                                <x-ui.empty-state
                                    title="Aucune classe dans ce niveau"
                                    description="Ajoutez une première classe pour commencer à affecter des élèves."
                                />
                            @else
                                <div class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
                                    @foreach($lvl->classrooms as $c)
                                        @php
                                            $cSearch = strtolower($lvl->name.' '.$lvl->code.' '.$c->name.' '.$c->section);
                                        @endphp
                                        <article class="class-item rounded-[22px] border border-slate-200 bg-slate-50/70 p-5 shadow-sm transition hover:border-slate-300 hover:bg-white"
                                                 data-search="{{ $cSearch }}">
                                            <div class="flex items-start justify-between gap-4">
                                                <div class="min-w-0">
                                                    <h4 class="truncate text-base font-semibold text-slate-900">
                                                        {{ $c->name }}
                                                        @if($c->section)
                                                            <span class="text-sm font-medium text-slate-500">({{ $c->section }})</span>
                                                        @endif
                                                    </h4>
                                                    <div class="mt-3 flex flex-wrap gap-2">
                                                        <x-ui.badge variant="info">{{ $c->students_count }} élève(s)</x-ui.badge>
                                                        <x-ui.badge variant="warning">Ordre : {{ $c->sort_order ?? '-' }}</x-ui.badge>
                                                    </div>
                                                </div>
                                                <a href="{{ route('admin.structure.classrooms.show', $c) }}" class="app-button-ghost min-h-10 rounded-full px-3 py-2 text-xs">
                                                    Ouvrir
                                                </a>
                                            </div>

                                            <div class="mt-5 flex flex-wrap justify-end gap-2">
                                                <details class="relative">
                                                    <summary class="list-none cursor-pointer app-button-secondary min-h-10 rounded-full px-4 py-2 text-xs">
                                                        Modifier
                                                    </summary>
                                                    <div class="absolute right-0 z-20 mt-3 w-[20rem] rounded-2xl border border-slate-200 bg-white p-4 shadow-[0_28px_70px_-40px_rgba(15,23,42,0.34)]">
                                                        <form method="POST" action="{{ route('admin.structure.classrooms.update', $c) }}" class="space-y-4">
                                                            @csrf
                                                            @method('PUT')

                                                            <div class="app-field">
                                                                <label class="app-label">Nom</label>
                                                                <input name="name" value="{{ $c->name }}" class="app-input">
                                                            </div>

                                                            <div class="app-field">
                                                                <label class="app-label">Section</label>
                                                                <input name="section" value="{{ $c->section }}" class="app-input">
                                                                <p class="app-hint">Optionnel. Générée automatiquement sinon.</p>
                                                            </div>

                                                            <div class="flex justify-end">
                                                                <x-ui.button type="submit" variant="primary" size="sm">Enregistrer</x-ui.button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </details>

                                                <form method="POST" action="{{ route('admin.structure.classrooms.destroy', $c) }}"
                                                      onsubmit="return confirm('Supprimer cette classe ? (elle doit être vide)')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <x-ui.button type="submit" variant="danger" size="sm">Supprimer</x-ui.button>
                                                </form>
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </details>
                @endforeach
            </div>
        @endif
    </section>

    <script>
        (function () {
            const input = document.getElementById('searchBox');
            const levelsWrap = document.getElementById('levelsWrap');
            if (!input || !levelsWrap) return;

            function applyFilter() {
                const q = (input.value || '').trim().toLowerCase();
                const levelItems = Array.from(levelsWrap.querySelectorAll('.level-item'));

                levelItems.forEach(level => {
                    const levelText = level.getAttribute('data-search') || '';
                    const classItems = Array.from(level.querySelectorAll('.class-item'));

                    if (!q) {
                        level.classList.remove('hidden');
                        classItems.forEach(c => c.classList.remove('hidden'));
                        return;
                    }

                    let anyClassVisible = false;
                    classItems.forEach(c => {
                        const t = c.getAttribute('data-search') || '';
                        const ok = t.includes(q);
                        c.classList.toggle('hidden', !ok);
                        if (ok) anyClassVisible = true;
                    });

                    const showLevel = levelText.includes(q) || anyClassVisible;
                    level.classList.toggle('hidden', !showLevel);
                });
            }

            input.addEventListener('input', applyFilter);
        })();
    </script>
</x-admin-layout>
