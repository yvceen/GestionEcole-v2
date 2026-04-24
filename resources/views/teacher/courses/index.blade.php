{{-- resources/views/teacher/courses/index.blade.php --}}
<x-teacher-layout title="Mes cours">
    <x-slot name="header">Mes cours</x-slot>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-6">
        {{-- Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-slate-900">Mes cours</h1>
                <p class="mt-1 text-sm text-slate-500">
                    Publiez des supports (PDF, images, docs…) pour vos classes.
                </p>
            </div>

            <div class="flex gap-2">
                @if(\Illuminate\Support\Facades\Route::has('teacher.courses.create'))
                    <a href="{{ route('teacher.courses.create') }}"
                       class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-black">
                        + Ajouter un cours
                    </a>
                @endif
            </div>
        </div>

        {{-- Flash --}}
        @if(session('success'))
            <div class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800 text-sm">
                <span class="font-semibold">OK :</span> {{ session('success') }}
            </div>
        @endif

        {{-- Stats (safe) --}}
        @php
            $totalCourses = method_exists($courses, 'total') ? $courses->total() : (is_countable($courses) ? count($courses) : 0);
            $pageCount = is_countable($courses) ? count($courses) : 0;
            $attachmentsOnPage = 0;

            try {
                foreach($courses as $cc) { $attachmentsOnPage += ($cc->attachments?->count() ?? 0); }
            } catch (\Throwable $e) {}
        @endphp

        <div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="rounded-[28px] border border-black/5 bg-white/70 backdrop-blur-xl p-5 shadow-[0_18px_45px_-35px_rgba(0,0,0,.45)]">
                <div class="text-xs uppercase tracking-wider text-slate-500">Cours (total)</div>
                <div class="mt-2 text-3xl font-semibold text-slate-900">{{ $totalCourses }}</div>
                <div class="mt-1 text-xs text-slate-500">Dans votre espace enseignant</div>
            </div>

            <div class="rounded-[28px] border border-black/5 bg-white/70 backdrop-blur-xl p-5 shadow-[0_18px_45px_-35px_rgba(0,0,0,.45)]">
                <div class="text-xs uppercase tracking-wider text-slate-500">Cours (cette page)</div>
                <div class="mt-2 text-3xl font-semibold text-slate-900">{{ $pageCount }}</div>
                <div class="mt-1 text-xs text-slate-500">Résultats affichés</div>
            </div>

            <div class="rounded-[28px] border border-black/5 bg-white/70 backdrop-blur-xl p-5 shadow-[0_18px_45px_-35px_rgba(0,0,0,.45)]">
                <div class="text-xs uppercase tracking-wider text-slate-500">Pièces jointes (page)</div>
                <div class="mt-2 text-3xl font-semibold text-slate-900">{{ $attachmentsOnPage }}</div>
                <div class="mt-1 text-xs text-slate-500">Total des fichiers visibles</div>
            </div>
        </div>

        {{-- Filters --}}
        <form method="GET" class="mt-6 rounded-[28px] border border-black/10 bg-white/80 backdrop-blur-xl p-5 shadow-sm">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                <div class="md:col-span-5">
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Recherche</label>
                    <input
                        type="text"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Titre, description…"
                        class="w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm"
                    />
                </div>

                <div class="md:col-span-4">
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Classe</label>
                    <select name="classroom_id" class="w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm">
                        <option value="">Toutes mes classes</option>
                        @foreach(($classrooms ?? []) as $cl)
                            <option value="{{ $cl->id }}" @selected((string)request('classroom_id') === (string)$cl->id)>
                                {{ $cl->name }} @if($cl->level?->name) — {{ $cl->level->name }} @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-3">
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Période</label>
                    @php $p = request('period','all'); @endphp
                    <select name="period" class="w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm">
                        <option value="all" @selected($p==='all')>Tout</option>
                        <option value="week" @selected($p==='week')>Cette semaine</option>
                        <option value="month" @selected($p==='month')>Ce mois</option>
                        <option value="year" @selected($p==='year')>Cette année</option>
                    </select>
                </div>
            </div>

            <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div class="text-xs text-slate-500">
                    Astuce : utilisez <span class="font-semibold text-slate-700">Recherche</span> + <span class="font-semibold text-slate-700">Classe</span> pour retrouver vite un cours.
                </div>

                <div class="flex gap-2">
                    <button class="rounded-2xl bg-slate-900 px-6 py-2.5 text-sm font-semibold text-white hover:bg-black">
                        Filtrer
                    </button>
                    <a href="{{ url()->current() }}"
                       class="rounded-2xl border border-black/10 bg-white px-6 py-2.5 text-sm font-semibold hover:bg-slate-50">
                        Réinitialiser
                    </a>
                </div>
            </div>
        </form>

        {{-- Grid --}}
        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
            @forelse($courses as $c)
                @php
                    $className = $c->classroom?->name ?? '—';
                    $levelName = $c->classroom?->level?->name ?? '—';
                    $filesCount = $c->attachments?->count() ?? 0;

                    $dateLabel = $c->created_at ? $c->created_at->format('d/m/Y') : null;
                @endphp

                <div class="group rounded-[28px] border border-black/5 bg-white/80 backdrop-blur-xl p-5 shadow-[0_18px_45px_-35px_rgba(0,0,0,.45)] hover:bg-white transition">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <div class="truncate text-base font-semibold text-slate-900">
                                    {{ $c->title }}
                                </div>

                                {{-- Badge fichiers --}}
                                <span class="shrink-0 inline-flex items-center rounded-full border border-black/10 bg-slate-50 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                    {{ $filesCount }} fichier{{ $filesCount > 1 ? 's' : '' }}
                                </span>
                            </div>

                            <div class="mt-1 text-xs text-slate-500 truncate">
                                {{ $className }} • {{ $levelName }}
                            </div>
                        </div>

                        <div class="text-right shrink-0">
                            @if($dateLabel)
                                <div class="text-xs text-slate-500">{{ $dateLabel }}</div>
                            @endif

                            {{-- Actions (safe) --}}
                            <div class="mt-2 flex items-center justify-end gap-2 opacity-80 group-hover:opacity-100">
                                @if(\Illuminate\Support\Facades\Route::has('teacher.courses.edit'))
                                    <a href="{{ route('teacher.courses.edit', $c) }}"
                                       class="rounded-xl border border-black/10 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                        Modifier
                                    </a>
                                @endif

                                @if(\Illuminate\Support\Facades\Route::has('teacher.courses.destroy'))
                                    <form method="POST" action="{{ route('teacher.courses.destroy', $c) }}"
                                          onsubmit="return confirm('Supprimer ce cours ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100">
                                            Supprimer
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($c->description)
                        <p class="mt-3 text-sm text-slate-700 line-clamp-3">
                            {{ $c->description }}
                        </p>
                    @else
                        <p class="mt-3 text-sm text-slate-400 italic">
                            Aucune description.
                        </p>
                    @endif

                    {{-- Attachments preview (safe) --}}
                    @if($filesCount > 0)
                        <div class="mt-4">
                            <div class="text-xs font-semibold text-slate-600">Pièces jointes</div>

                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach($c->attachments->take(6) as $a)
                                    @php
                                        $name = $a->original_name ?? $a->name ?? 'Fichier';
                                        $ext = pathinfo($name, PATHINFO_EXTENSION);
                                    @endphp
                                    <span class="inline-flex items-center rounded-full border border-black/10 bg-white px-3 py-1 text-xs font-semibold text-slate-700">
                                        @if($ext)
                                            <span class="mr-2 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600">{{ strtoupper($ext) }}</span>
                                        @endif
                                        <span class="max-w-[220px] truncate">{{ $name }}</span>
                                    </span>
                                @endforeach

                                @if($filesCount > 6)
                                    <span class="inline-flex items-center rounded-full border border-black/10 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-600">
                                        +{{ $filesCount - 6 }} autres
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endif

                    <div class="mt-4 flex items-center justify-between text-xs text-slate-500">
                        <div>
                            Classe : <span class="font-semibold text-slate-700">{{ $className }}</span>
                        </div>
                        <div class="opacity-70">
                            ID : {{ $c->id }}
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-[28px] border border-black/5 bg-white/80 p-10 text-center text-slate-600">
                    <div class="text-lg font-semibold text-slate-900">Aucun cours pour le moment</div>
                    <div class="mt-1 text-sm text-slate-500">Commencez par publier un support pour une classe.</div>

                    @if(\Illuminate\Support\Facades\Route::has('teacher.courses.create'))
                        <a href="{{ route('teacher.courses.create') }}"
                           class="mt-5 inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-black">
                            + Ajouter mon premier cours
                        </a>
                    @endif
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $courses->withQueryString()->links() }}
        </div>
    </div>
</x-teacher-layout>
