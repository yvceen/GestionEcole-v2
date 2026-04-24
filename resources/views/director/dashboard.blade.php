<x-director-layout title="Tableau de bord - Direction">
    @php
        $panel = "rounded-[28px] border border-black/5 bg-white/70 backdrop-blur-2xl shadow-[0_18px_45px_-35px_rgba(0,0,0,.45)]";
        $soft = "border border-black/5 bg-white/60";

        $badge = function (string $text, string $tone = 'slate') {
            $map = [
                'green' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                'red' => 'bg-red-50 text-red-800 border-red-200',
                'amber' => 'bg-amber-50 text-amber-800 border-amber-200',
                'blue' => 'bg-sky-50 text-sky-800 border-sky-200',
                'slate' => 'bg-slate-100 text-slate-800 border-slate-200',
                'purple' => 'bg-violet-50 text-violet-800 border-violet-200',
            ];

            $cls = $map[$tone] ?? $map['slate'];

            return "<span class=\"inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold {$cls}\">{$text}</span>";
        };

        $fmtDate = fn ($date) => \Carbon\Carbon::parse($date)->translatedFormat('d M Y');

        $statusTone = match ($globalStatus ?? 'OK') {
            'CRITIQUE' => 'red',
            'ATTENTION' => 'amber',
            default => 'green',
        };

        $statusLabel = match ($globalStatus ?? 'OK') {
            'CRITIQUE' => 'Etat global : Critique',
            'ATTENTION' => 'Etat global : A surveiller',
            default => 'Etat global : OK',
        };

        $coursesTone = ($coverageCoursesPct ?? 0) < 60 ? 'red' : (($coverageCoursesPct ?? 0) < 85 ? 'amber' : 'green');
        $homeworksTone = ($coverageHomeworksPct ?? 0) < 60 ? 'red' : (($coverageHomeworksPct ?? 0) < 85 ? 'amber' : 'green');
    @endphp

    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900 sm:text-3xl">Tableau de bord</h1>
                {!! $badge($statusLabel, $statusTone) !!}
            </div>

            <p class="mt-1 text-sm text-slate-500">
                Semaine : {{ $fmtDate($startOfWeek) }} -> {{ $fmtDate($endOfWeek) }}
                <span class="mx-2 text-slate-300">|</span>
                Vue direction pour le suivi pedagogique et la vigilance quotidienne.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('director.results.index') }}"
               class="rounded-2xl border border-black/10 bg-white px-5 py-2.5 text-sm font-semibold hover:bg-slate-50">
                Voir les resultats
            </a>
            <a href="{{ route('director.monitoring') }}"
               class="rounded-2xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-black">
                Ouvrir le monitoring
            </a>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-6">
        <div class="{{ $panel }} p-5">
            <div class="text-xs uppercase tracking-wider text-slate-500">Eleves</div>
            <div class="mt-2 flex items-end justify-between">
                <div class="text-3xl font-semibold text-slate-900">{{ $studentsCount }}</div>
                {!! $badge('Total inscrits', 'blue') !!}
            </div>
            <div class="mt-2 text-xs text-slate-500">Nombre total d'eleves rattaches a l'etablissement.</div>
        </div>

        <div class="{{ $panel }} p-5">
            <div class="text-xs uppercase tracking-wider text-slate-500">Classes</div>
            <div class="mt-2 flex items-end justify-between">
                <div class="text-3xl font-semibold text-slate-900">{{ $classroomsCount }}</div>
                {!! $badge('Actives', 'purple') !!}
            </div>
            <div class="mt-2 text-xs text-slate-500">Classes disponibles pour le suivi hebdomadaire.</div>
        </div>

        <div class="{{ $panel }} p-5">
            <div class="text-xs uppercase tracking-wider text-slate-500">Enseignants</div>
            <div class="mt-2 flex items-end justify-between">
                <div class="text-3xl font-semibold text-slate-900">{{ $teachersCount }}</div>
                <div class="flex flex-wrap gap-2">
                    {!! $badge('Actifs : '.($activeTeachers ?? 0), 'green') !!}
                    @if(($inactiveTeachers ?? 0) > 0)
                        {!! $badge('Inactifs : '.$inactiveTeachers, 'amber') !!}
                    @endif
                </div>
            </div>
            <div class="mt-2 text-xs text-slate-500">Effectif enseignant et statut d'activite.</div>
        </div>

        <div class="{{ $panel }} p-5">
            <div class="text-xs uppercase tracking-wider text-slate-500">Parents</div>
            <div class="mt-2 flex items-end justify-between">
                <div class="text-3xl font-semibold text-slate-900">{{ $parentsCount }}</div>
                {!! $badge('Comptes lies', 'slate') !!}
            </div>
            <div class="mt-2 text-xs text-slate-500">Parents ayant un compte relie a un eleve.</div>
        </div>

        <div class="{{ $panel }} p-5">
            <div class="text-xs uppercase tracking-wider text-slate-500">Absences du jour</div>
            <div class="mt-2 flex items-end justify-between">
                <div class="text-3xl font-semibold text-rose-700">{{ (int) ($attendanceSummary['today_absent'] ?? 0) }}</div>
                {!! $badge('Aujourd hui', 'red') !!}
            </div>
            <div class="mt-2 text-xs text-slate-500">Absences declarees sur la journee courante.</div>
        </div>

        <div class="{{ $panel }} p-5">
            <div class="text-xs uppercase tracking-wider text-slate-500">Retards du jour</div>
            <div class="mt-2 flex items-end justify-between">
                <div class="text-3xl font-semibold text-amber-700">{{ (int) ($attendanceSummary['today_late'] ?? 0) }}</div>
                {!! $badge('A surveiller', 'amber') !!}
            </div>
            <div class="mt-2 text-xs text-slate-500">Retards remontes par l'equipe pedagogique.</div>
        </div>
    </div>

    <div class="mt-4 {{ $panel }} p-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="text-sm font-semibold text-slate-900">Vue hebdomadaire des presences</div>
                <div class="mt-1 text-xs text-slate-500">Absences et retards cumules par jour sur l'ecole active.</div>
            </div>
            <a href="{{ route('director.attendance.index') }}"
               class="rounded-2xl border border-black/10 bg-white px-5 py-2.5 text-sm font-semibold hover:bg-slate-50">
                Ouvrir le monitoring des presences
            </a>
        </div>

        <div class="mt-4 grid gap-3 md:grid-cols-7">
            @foreach(($attendanceSummary['weekly_overview'] ?? collect()) as $day)
                <div class="rounded-2xl {{ $soft }} p-4">
                    <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ $day['label'] }}</div>
                    <div class="mt-3 space-y-2 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Absences</span>
                            <span class="font-semibold text-rose-700">{{ $day['absent'] }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Retards</span>
                            <span class="font-semibold text-amber-700">{{ $day['late'] }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-3">
        <div class="{{ $panel }} p-6">
            <div class="flex items-center justify-between">
                <div class="text-sm font-semibold text-slate-900">Activite de la semaine</div>
                <div class="text-xs text-slate-500">Cours et devoirs</div>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div class="rounded-2xl {{ $soft }} p-4">
                    <div class="text-xs font-semibold text-slate-500">Cours publies</div>
                    <div class="mt-1 text-2xl font-semibold text-slate-900">{{ $coursesThisWeek }}</div>
                    <div class="mt-2">{!! $badge('Couverture classes : '.$coverageCoursesPct.'%', $coursesTone) !!}</div>
                </div>

                <div class="rounded-2xl {{ $soft }} p-4">
                    <div class="text-xs font-semibold text-slate-500">Devoirs publies</div>
                    <div class="mt-1 text-2xl font-semibold text-slate-900">{{ $homeworksThisWeek }}</div>
                    <div class="mt-2">{!! $badge('Couverture enseignants : '.$coverageHomeworksPct.'%', $homeworksTone) !!}</div>
                </div>
            </div>

            <div class="mt-4 rounded-2xl {{ $soft }} p-4">
                <div class="mb-2 text-xs font-semibold text-slate-500">Lecture rapide</div>
                <ul class="space-y-2 text-sm text-slate-700">
                    <li class="flex items-start gap-2">
                        <span class="mt-1 inline-block h-2.5 w-2.5 rounded-full bg-slate-900"></span>
                        <span>{!! $badge($coverageCoursesPct.'%', $coursesTone) !!} des classes ont au moins <span class="font-semibold">1 cours</span> cette semaine.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="mt-1 inline-block h-2.5 w-2.5 rounded-full bg-slate-900"></span>
                        <span>{!! $badge($coverageHomeworksPct.'%', $homeworksTone) !!} des enseignants ont publie au moins <span class="font-semibold">1 devoir</span> cette semaine.</span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="{{ $panel }} p-6">
            <div class="text-sm font-semibold text-slate-900">Alertes intelligentes</div>
            <div class="mt-1 text-xs text-slate-500">Ecarts detectes automatiquement sur la semaine.</div>

            <div class="mt-4 space-y-3">
                <div class="rounded-2xl {{ $soft }} p-4">
                    <div class="flex items-center justify-between">
                        <div class="text-xs font-semibold text-slate-500">Classes sans cours</div>
                        {!! $badge('Priorite', ($classroomsNoCourses->count() ?? 0) ? 'amber' : 'green') !!}
                    </div>

                    <div class="mt-2 flex flex-wrap gap-2">
                        @forelse($classroomsNoCourses as $classroom)
                            {!! $badge($classroom->name, 'amber') !!}
                        @empty
                            <span class="text-sm text-slate-600">Toutes les classes ont au moins un cours.</span>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-2xl {{ $soft }} p-4">
                    <div class="flex items-center justify-between">
                        <div class="text-xs font-semibold text-slate-500">Enseignants sans devoir</div>
                        {!! $badge('Suivi', ($teachersNoHomeworks->count() ?? 0) ? 'amber' : 'green') !!}
                    </div>

                    <div class="mt-2 flex flex-wrap gap-2">
                        @forelse($teachersNoHomeworks as $teacher)
                            {!! $badge($teacher->name, ((int) ($teacher->is_active ?? 1) === 1) ? 'amber' : 'slate') !!}
                        @empty
                            <span class="text-sm text-slate-600">Tous les enseignants ont publie des devoirs.</span>
                        @endforelse
                    </div>

                    <div class="mt-3 text-xs text-slate-500">
                        Utilisez cette liste pour prioriser les relances pedagogiques.
                    </div>
                </div>
            </div>
        </div>

        <div class="{{ $panel }} p-6">
            <div class="flex items-center justify-between">
                <div class="text-sm font-semibold text-slate-900">Classes a la traine</div>
                <div class="text-xs text-slate-500">Top 20 activite faible</div>
            </div>

            <div class="mt-4 space-y-2">
                @forelse($laggingClassrooms as $classroom)
                    @php
                        $activity = (int) ($classroom->activity_week ?? 0);
                        $tone = $activity === 0 ? 'red' : ($activity <= 1 ? 'amber' : 'green');
                    @endphp

                    <div class="flex items-center justify-between rounded-2xl {{ $soft }} px-4 py-3">
                        <div class="min-w-0">
                            <div class="truncate text-sm font-semibold text-slate-900">{{ $classroom->name }}</div>
                            <div class="text-xs text-slate-500">
                                Enseignants : {{ $classroom->teachers_count ?? 0 }}
                                <span class="mx-2 text-slate-300">|</span>
                                {!! $badge('Activite : '.$activity, $tone) !!}
                            </div>
                        </div>

                        <div class="text-right text-xs text-slate-700">
                            <div class="font-semibold">Cours : {{ $classroom->courses_week }}</div>
                            <div class="text-slate-500">Devoirs : {{ $classroom->homeworks_week }}</div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl {{ $soft }} p-6 text-sm text-slate-600">
                        Aucune donnee pour le classement cette semaine.
                    </div>
                @endforelse
            </div>

            <div class="mt-4 text-xs text-slate-500">
                Conseil : combinez cette vue avec le monitoring des presences pour identifier les classes qui demandent un accompagnement plus fort.
            </div>
        </div>
    </div>
</x-director-layout>
