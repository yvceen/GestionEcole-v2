<x-dynamic-component :component="$layoutComponent" title="Structure scolaire">
    @php
        $totalClasses = $levels->sum(fn ($level) => $level->classrooms->count());
        $totalStudents = $levels->sum(fn ($level) => $level->classrooms->sum('students_count'));
        $route = fn (string $name, $parameters = []) => route($routePrefix.'.'.$name, $parameters);
        $cycleStyles = [
            'rose' => 'border-rose-200 bg-rose-50 text-rose-700',
            'sky' => 'border-sky-200 bg-sky-50 text-sky-700',
            'amber' => 'border-amber-200 bg-amber-50 text-amber-700',
            'violet' => 'border-violet-200 bg-violet-50 text-violet-700',
            'emerald' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            'slate' => 'border-slate-200 bg-slate-50 text-slate-700',
        ];
    @endphp

    @if($errors->any())
        <x-ui.alert variant="error">
            <div class="font-semibold">Certaines actions n'ont pas pu être appliquées.</div>
            <ul class="mt-2 list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </x-ui.alert>
    @endif

    <section class="app-card overflow-hidden p-0">
        <div class="border-b border-slate-200 bg-slate-950 px-6 py-7 text-white md:px-8">
            <div class="flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
                <div class="max-w-3xl">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-sky-300">Organisation pédagogique</p>
                    <h2 class="mt-3 text-3xl font-semibold">Cycles, niveaux et classes</h2>
                    <p class="mt-3 text-sm leading-6 text-slate-300">Organisez l'établissement par cycle, créez les niveaux, puis séparez-les en classes comme PS-A, PS-B ou 1AP-A.</p>
                </div>
                <form method="POST" action="{{ $route('presets.store') }}">
                    @csrf
                    <button class="app-button-secondary border-white/20 bg-white text-slate-900" type="submit">Installer les niveaux standards</button>
                </form>
            </div>
        </div>
        <div class="grid gap-3 p-5 sm:grid-cols-2 xl:grid-cols-4">
            @foreach([['Cycles', $cycles->count()], ['Niveaux', $levels->count()], ['Classes', $totalClasses], ['Élèves', $totalStudents]] as [$label, $value])
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-5 py-4">
                    <div class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">{{ $label }}</div>
                    <div class="mt-2 text-3xl font-semibold text-slate-950">{{ $value }}</div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
        <div class="space-y-5">
            <div class="app-card flex flex-col gap-4 px-5 py-5 md:flex-row md:items-center md:justify-between">
                <div><p class="app-overline">Structure active</p><h2 class="mt-2 text-xl font-semibold text-slate-950">Organisation par cycle</h2></div>
                <input id="structureSearch" class="app-input md:max-w-sm" placeholder="Rechercher un cycle, niveau ou classe...">
            </div>

            <div id="structureTree" class="space-y-5">
                @foreach($cycles as $cycle)
                    @php
                        $cycleClassrooms = $cycle->levels->sum(fn ($level) => $level->classrooms->count());
                        $cycleStudents = $cycle->levels->sum(fn ($level) => $level->classrooms->sum('students_count'));
                        $style = $cycleStyles[$cycle->color] ?? $cycleStyles['slate'];
                    @endphp
                    <article class="structure-cycle app-card overflow-hidden" data-search="{{ strtolower($cycle->name.' '.$cycle->code.' '.$cycle->levels->pluck('name')->join(' ').' '.$cycle->levels->flatMap->classrooms->pluck('name')->join(' ')) }}">
                        <div class="flex flex-col gap-4 border-b border-slate-200 px-6 py-5 lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex items-center gap-4">
                                <div class="grid h-12 w-12 place-items-center rounded-xl border text-sm font-bold {{ $style }}">{{ $cycle->code }}</div>
                                <div>
                                    <h3 class="text-xl font-semibold text-slate-950">{{ $cycle->name }}</h3>
                                    <p class="mt-1 text-xs text-slate-500">{{ $cycle->levels->count() }} niveaux · {{ $cycleClassrooms }} classes · {{ $cycleStudents }} élèves</p>
                                </div>
                            </div>
                            <details class="relative">
                                <summary class="app-button-secondary cursor-pointer list-none">Modifier</summary>
                                <div class="absolute right-0 z-30 mt-2 w-80 rounded-xl border border-slate-200 bg-white p-4 shadow-xl">
                                    <form method="POST" action="{{ $route('cycles.update', $cycle) }}" class="space-y-3">
                                        @csrf @method('PUT')
                                        <input name="name" value="{{ $cycle->name }}" class="app-input" required>
                                        <input name="code" value="{{ $cycle->code }}" class="app-input" required>
                                        <select name="color" class="app-input">@foreach(array_keys($cycleStyles) as $color)<option value="{{ $color }}" @selected($cycle->color === $color)>{{ ucfirst($color) }}</option>@endforeach</select>
                                        <button class="app-button-primary" type="submit">Enregistrer</button>
                                    </form>
                                    @if($cycle->levels->isEmpty())
                                        <form method="POST" action="{{ $route('cycles.destroy', $cycle) }}" class="mt-3" onsubmit="return confirm('Supprimer ce cycle ?')">
                                            @csrf @method('DELETE')
                                            <button class="app-button-danger w-full" type="submit">Supprimer le cycle</button>
                                        </form>
                                    @endif
                                </div>
                            </details>
                        </div>

                        <div class="space-y-4 bg-slate-50/70 p-5">
                            @forelse($cycle->levels as $level)
                                <details class="rounded-xl border border-slate-200 bg-white" open>
                                    <summary class="cursor-pointer list-none px-5 py-4">
                                        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                            <div>
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="rounded-lg bg-slate-950 px-2.5 py-1 text-xs font-bold text-white">{{ $level->code }}</span>
                                                    <h4 class="font-semibold text-slate-950">{{ $level->name }}</h4>
                                                </div>
                                                <p class="mt-2 text-xs text-slate-500">{{ $level->classrooms->count() }} classes · {{ $level->classrooms->sum('students_count') }} élèves</p>
                                            </div>
                                            <details class="relative">
                                                <summary class="cursor-pointer list-none text-xs font-semibold text-sky-700">Gérer le niveau</summary>
                                                <div class="absolute right-0 z-20 mt-2 w-80 rounded-xl border border-slate-200 bg-white p-4 shadow-xl">
                                                    <form method="POST" action="{{ $route('levels.update', $level) }}" class="space-y-3">
                                                        @csrf @method('PUT')
                                                        <select name="education_cycle_id" class="app-input" required>
                                                            @foreach($cycles as $cycleOption)<option value="{{ $cycleOption->id }}" @selected($cycleOption->id === $level->education_cycle_id)>{{ $cycleOption->name }}</option>@endforeach
                                                        </select>
                                                        <div class="grid grid-cols-[90px_1fr] gap-2">
                                                            <input name="code" value="{{ $level->code }}" class="app-input" required>
                                                            <input name="name" value="{{ $level->name }}" class="app-input" required>
                                                        </div>
                                                        <button class="app-button-primary" type="submit">Enregistrer</button>
                                                    </form>
                                                    @if($level->classrooms->isEmpty())
                                                        <form method="POST" action="{{ $route('levels.destroy', $level) }}" class="mt-3" onsubmit="return confirm('Supprimer ce niveau ?')">
                                                            @csrf @method('DELETE')
                                                            <button class="app-button-danger w-full" type="submit">Supprimer le niveau</button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </details>
                                        </div>
                                    </summary>
                                    <div class="border-t border-slate-200 p-4">
                                        <div class="grid gap-3 sm:grid-cols-2 2xl:grid-cols-3">
                                            @forelse($level->classrooms as $classroom)
                                                @php $isFull = $classroom->capacity && $classroom->students_count >= $classroom->capacity; @endphp
                                                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                                    <div class="flex items-start justify-between gap-3">
                                                        <div>
                                                            <a href="{{ $route('classrooms.show', $classroom) }}" class="font-semibold text-slate-950 hover:text-sky-700">{{ $classroom->name }}</a>
                                                            <p class="mt-1 text-xs text-slate-500">Section {{ $classroom->section }}</p>
                                                        </div>
                                                        <span class="rounded-full px-2 py-1 text-[0.68rem] font-semibold {{ $isFull ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700' }}">{{ $classroom->students_count }}{{ $classroom->capacity ? '/'.$classroom->capacity : '' }}</span>
                                                    </div>
                                                    <details class="mt-4">
                                                        <summary class="cursor-pointer list-none text-xs font-semibold text-sky-700">Modifier</summary>
                                                        <form method="POST" action="{{ $route('classrooms.update', $classroom) }}" class="mt-3 space-y-3 rounded-xl border border-slate-200 bg-slate-50 p-3">
                                                            @csrf @method('PUT')
                                                            <input name="name" value="{{ $classroom->name }}" class="app-input" required>
                                                            <div class="grid grid-cols-2 gap-2">
                                                                <input name="section" value="{{ $classroom->section }}" class="app-input" placeholder="A">
                                                                <input name="capacity" value="{{ $classroom->capacity }}" type="number" min="1" max="200" class="app-input" placeholder="Capacité">
                                                            </div>
                                                            <button class="app-button-primary" type="submit">Enregistrer</button>
                                                        </form>
                                                        @if($classroom->students_count === 0)
                                                            <form method="POST" action="{{ $route('classrooms.destroy', $classroom) }}" class="mt-2" onsubmit="return confirm('Supprimer cette classe ?')">
                                                                @csrf @method('DELETE')
                                                                <button class="app-button-danger w-full" type="submit">Supprimer la classe</button>
                                                            </form>
                                                        @endif
                                                    </details>
                                                </div>
                                            @empty
                                                <div class="col-span-full rounded-xl border border-dashed border-slate-300 px-4 py-5 text-sm text-slate-500">Aucune classe dans ce niveau.</div>
                                            @endforelse
                                        </div>
                                    </div>
                                </details>
                            @empty
                                <div class="rounded-xl border border-dashed border-slate-300 bg-white px-5 py-6 text-sm text-slate-500">Aucun niveau dans ce cycle.</div>
                            @endforelse
                        </div>
                    </article>
                @endforeach
            </div>
        </div>

        <aside class="space-y-5">
            <div class="app-card px-5 py-5">
                <p class="app-overline">Création rapide</p>
                <h2 class="mt-2 text-lg font-semibold text-slate-950">Créer plusieurs classes</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">Saisissez A, B, C pour générer automatiquement PS-A, PS-B et PS-C.</p>
                <form method="POST" action="{{ $route('classrooms.bulk.store') }}" class="mt-5 space-y-4">
                    @csrf
                    <select name="level_id" class="app-input" required>
                        <option value="">Choisir un niveau</option>
                        @foreach($cycles as $cycle)<optgroup label="{{ $cycle->name }}">@foreach($cycle->levels as $level)<option value="{{ $level->id }}">{{ $level->code }} — {{ $level->name }}</option>@endforeach</optgroup>@endforeach
                    </select>
                    <input name="sections" class="app-input" placeholder="A, B, C" required>
                    <input name="capacity" type="number" min="1" max="200" class="app-input" placeholder="Capacité par classe (optionnel)">
                    <button class="app-button-primary w-full" type="submit">Créer les classes</button>
                </form>
            </div>

            <div class="app-card px-5 py-5">
                <p class="app-overline">Nouveau niveau</p>
                <form method="POST" action="{{ $route('levels.store') }}" class="mt-4 space-y-3">
                    @csrf
                    <select name="education_cycle_id" class="app-input" required><option value="">Cycle</option>@foreach($cycles as $cycle)<option value="{{ $cycle->id }}">{{ $cycle->name }}</option>@endforeach</select>
                    <div class="grid grid-cols-[100px_1fr] gap-2"><input name="code" class="app-input" placeholder="PS" required><input name="name" class="app-input" placeholder="Petite section" required></div>
                    <button class="app-button-secondary w-full" type="submit">Ajouter le niveau</button>
                </form>
            </div>

            <details class="app-card px-5 py-5">
                <summary class="cursor-pointer list-none">
                    <p class="app-overline">Classe personnalisée</p>
                    <h2 class="mt-2 text-lg font-semibold text-slate-950">Créer une classe manuellement</h2>
                </summary>
                <form method="POST" action="{{ $route('classrooms.store') }}" class="mt-4 space-y-3">
                    @csrf
                    <select name="level_id" class="app-input" required>
                        <option value="">Choisir un niveau</option>
                        @foreach($cycles as $cycle)<optgroup label="{{ $cycle->name }}">@foreach($cycle->levels as $level)<option value="{{ $level->id }}">{{ $level->code }} — {{ $level->name }}</option>@endforeach</optgroup>@endforeach
                    </select>
                    <input name="name" class="app-input" placeholder="Nom affiché, ex. PS-A" required>
                    <div class="grid grid-cols-2 gap-2">
                        <input name="section" class="app-input" placeholder="Section, ex. A">
                        <input name="capacity" type="number" min="1" max="200" class="app-input" placeholder="Capacité">
                    </div>
                    <button class="app-button-secondary w-full" type="submit">Créer la classe</button>
                </form>
            </details>

            <div class="app-card px-5 py-5">
                <p class="app-overline">Nouveau cycle</p>
                <form method="POST" action="{{ $route('cycles.store') }}" class="mt-4 space-y-3">
                    @csrf
                    <div class="grid grid-cols-[100px_1fr] gap-2"><input name="code" class="app-input" placeholder="SUP" required><input name="name" class="app-input" placeholder="Supérieur" required></div>
                    <select name="color" class="app-input">@foreach(array_keys($cycleStyles) as $color)<option value="{{ $color }}">{{ ucfirst($color) }}</option>@endforeach</select>
                    <button class="app-button-secondary w-full" type="submit">Ajouter le cycle</button>
                </form>
            </div>
        </aside>
    </section>

    <script>
        (() => {
            const input = document.getElementById('structureSearch');
            const items = [...document.querySelectorAll('.structure-cycle')];
            input?.addEventListener('input', () => {
                const query = input.value.trim().toLowerCase();
                items.forEach(item => item.classList.toggle('hidden', query && !item.dataset.search.includes(query)));
            });
        })();
    </script>
</x-dynamic-component>
