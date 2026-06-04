@php
    $layoutComponent = $layoutComponent ?? 'admin-layout';
    $routePrefix = $routePrefix ?? 'admin.timetable';
    $totalWeeklyMinutes = $slots->sum(function ($slot) {
        $start = \Carbon\Carbon::parse((string) $slot->start_time);
        $end = \Carbon\Carbon::parse((string) $slot->end_time);

        return max(0, $start->diffInMinutes($end));
    });
    $weeklyHours = round($totalWeeklyMinutes / 60, 1);
    $subjectCount = $slots->pluck('subject')->filter()->unique()->count();
    $teacherCount = $slots->pluck('teacher_id')->filter()->unique()->count();
    $unassignedTeacherCount = $slots->whereNull('teacher_id')->count();
    $missingRoomCount = $slots->filter(fn ($slot) => blank($slot->room))->count();
    $busiestDay = collect($days)
        ->map(fn ($label, $day) => ['label' => $label, 'count' => $slotsByDay->get($day, collect())->count()])
        ->sortByDesc('count')
        ->first();
@endphp

<x-dynamic-component :component="$layoutComponent" title="Emploi du temps">
    <section class="relative mb-6 overflow-hidden rounded-[26px] border border-sky-200 bg-[linear-gradient(135deg,#ffffff_0%,#f0f9ff_55%,#e0f2fe_100%)] p-6 shadow-sm">
        <div class="absolute -right-12 -top-16 h-48 w-48 rounded-full bg-sky-300/25 blur-3xl"></div>
        <div class="relative flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="app-badge app-badge-info">Organisation</span>
                    @if($currentAcademicYear)
                        <span class="app-badge">{{ $currentAcademicYear->name }}</span>
                    @endif
                </div>
                <h1 class="mt-4 text-2xl font-semibold tracking-tight text-slate-950 sm:text-3xl">Emploi du temps hebdomadaire</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
                    Organisez les cours de <strong class="text-slate-900">{{ $selectedClass?->name ?? 'la classe selectionnee' }}</strong>,
                    controlez les informations manquantes et ajustez les horaires directement dans la grille.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <x-ui.button :href="route($routePrefix . '.create', ['classroom_id' => $selectedClassroomId])" variant="primary">
                    Ajouter un creneau
                </x-ui.button>
                <x-ui.button :href="route($routePrefix . '.settings.edit')" variant="secondary">
                    Parametres
                </x-ui.button>
                <button type="button" onclick="window.print()" class="app-button-secondary">Imprimer</button>
            </div>
        </div>
    </section>

    <section class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <article class="app-card border-sky-100 p-4">
            <p class="text-[0.68rem] font-semibold uppercase tracking-[0.16em] text-sky-700">Creneaux</p>
            <div class="mt-2 flex items-end justify-between gap-3">
                <p class="text-3xl font-semibold text-slate-950">{{ $slots->count() }}</p>
                <span class="rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700">{{ $weeklyHours }} h / semaine</span>
            </div>
        </article>
        <article class="app-card border-indigo-100 p-4">
            <p class="text-[0.68rem] font-semibold uppercase tracking-[0.16em] text-indigo-700">Matieres</p>
            <div class="mt-2 flex items-end justify-between gap-3">
                <p class="text-3xl font-semibold text-slate-950">{{ $subjectCount }}</p>
                <span class="text-xs font-medium text-slate-500">dans la grille</span>
            </div>
        </article>
        <article class="app-card border-emerald-100 p-4">
            <p class="text-[0.68rem] font-semibold uppercase tracking-[0.16em] text-emerald-700">Enseignants</p>
            <div class="mt-2 flex items-end justify-between gap-3">
                <p class="text-3xl font-semibold text-slate-950">{{ $teacherCount }}</p>
                <span class="text-xs font-medium text-slate-500">assignes</span>
            </div>
        </article>
        <article class="app-card border-amber-100 p-4">
            <p class="text-[0.68rem] font-semibold uppercase tracking-[0.16em] text-amber-700">Jour le plus charge</p>
            <div class="mt-2 flex items-end justify-between gap-3">
                <p class="text-lg font-semibold text-slate-950">{{ ($busiestDay['count'] ?? 0) > 0 ? $busiestDay['label'] : 'Aucun' }}</p>
                <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">{{ $busiestDay['count'] ?? 0 }} cours</span>
            </div>
        </article>
    </section>

    <section class="app-card mb-6 overflow-hidden">
        <div class="grid lg:grid-cols-[minmax(0,1fr)_320px]">
            <form method="GET" action="{{ route($routePrefix . '.index') }}" class="p-5">
                <div class="mb-4">
                    <p class="text-sm font-semibold text-slate-900">Choisir une classe</p>
                    <p class="mt-1 text-xs text-slate-500">La grille et les indicateurs seront actualises pour la classe selectionnee.</p>
                </div>
                <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end">
                    <div>
                        <label for="classroom_id" class="mb-2 block text-sm font-semibold text-slate-700">Classe active</label>
                        <select id="classroom_id" name="classroom_id" class="app-input">
                            @foreach($classrooms as $classroom)
                                <option value="{{ $classroom->id }}" @selected((int) $selectedClassroomId === (int) $classroom->id)>
                                    {{ $classroom->name }} @if($classroom->level) ({{ $classroom->level->name }}) @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="app-button-secondary">Afficher la grille</button>
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach($classrooms->take(8) as $classroom)
                        <a
                            href="{{ route($routePrefix . '.index', ['classroom_id' => $classroom->id]) }}"
                            class="{{ (int) $selectedClassroomId === (int) $classroom->id ? 'border-sky-600 bg-sky-600 text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-sky-200 hover:bg-sky-50 hover:text-sky-800' }} rounded-full border px-3 py-1.5 text-xs font-semibold transition"
                        >
                            {{ $classroom->name }}
                        </a>
                    @endforeach
                </div>
            </form>

            <aside class="border-t border-slate-200 bg-slate-50/80 p-5 lg:border-l lg:border-t-0">
                <p class="text-sm font-semibold text-slate-900">Controle rapide</p>
                <div class="mt-4 space-y-2.5 text-sm">
                    <div class="flex items-center justify-between gap-3 rounded-xl bg-white px-3 py-2.5 ring-1 ring-slate-200">
                        <span class="text-slate-600">Protection des chevauchements</span>
                        <span class="font-semibold text-emerald-700">Active</span>
                    </div>
                    <div class="flex items-center justify-between gap-3 rounded-xl bg-white px-3 py-2.5 ring-1 ring-slate-200">
                        <span class="text-slate-600">Sans enseignant</span>
                        <span class="{{ $unassignedTeacherCount > 0 ? 'text-amber-700' : 'text-emerald-700' }} font-semibold">{{ $unassignedTeacherCount }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3 rounded-xl bg-white px-3 py-2.5 ring-1 ring-slate-200">
                        <span class="text-slate-600">Sans salle</span>
                        <span class="{{ $missingRoomCount > 0 ? 'text-amber-700' : 'text-emerald-700' }} font-semibold">{{ $missingRoomCount }}</span>
                    </div>
                </div>
            </aside>
        </div>
    </section>

    @if(session('success'))
        <section class="app-card mb-6 border-teal-200 bg-teal-50 p-4 text-sm text-teal-800">
            {{ session('success') }}
        </section>
    @endif

    @if($errors->any())
        <section class="app-card mb-6 border-red-200 bg-red-50 p-4 text-sm text-red-700">
            <ul class="space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </section>
    @endif

    @if($selectedClassroomId <= 0)
        <section class="app-card p-6 text-sm text-slate-600">
            Aucune classe active disponible.
        </section>
    @else
        @include('partials.timetable-grid', [
            'settings' => $settings,
            'slots' => $slots,
            'selectedClass' => $selectedClass,
            'days' => $days,
            'times' => $times,
            'slotsByDay' => $slotsByDay,
            'lunchBlock' => $lunchBlock,
            'totalMinutes' => $totalMinutes,
            'editable' => true,
            'editRouteName' => $routePrefix . '.edit',
            'deleteRouteName' => $routePrefix . '.destroy',
            'moveRouteName' => $routePrefix . '.move',
        ])
    @endif
</x-dynamic-component>
