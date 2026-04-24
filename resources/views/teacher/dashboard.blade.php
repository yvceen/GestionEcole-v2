<x-teacher-layout title="Espace Enseignant">
    <x-slot name="header">Tableau de bord</x-slot>

    @php
        // ✅ Defaults باش ما يطيحش view
        $startOfWeek = $startOfWeek ?? now()->startOfWeek();
        $endOfWeek   = $endOfWeek ?? now()->endOfWeek();

        $kpis = $kpis ?? [
            'courses_total' => 0,
            'courses_week' => 0,
            'grades_week' => 0,
            'attendance_week' => 0,
            'assessments_week' => 0,
        ];

        $alerts = $alerts ?? [
            'classrooms_no_courses' => collect(),
            'classrooms_no_grades'  => collect(),
            'hint' => "Publiez au moins 1 cours + 1 évaluation par classe chaque semaine.",
        ];

        $ranking = $ranking ?? [
            'top' => collect(),
            'low' => collect(),
        ];

        $latest = $latest ?? [
            'courses' => collect(),
            'grades' => collect(),
            'attendances' => collect(),
        ];

        $analysis = $analysis ?? [
            'avg' => null,
            'min' => null,
            'max' => null,
            'distribution' => [
                ['label'=>'0–5','value'=>0],
                ['label'=>'5–10','value'=>0],
                ['label'=>'10–15','value'=>0],
                ['label'=>'15–20','value'=>0],
            ],
        ];

        $pendingAttendanceClassrooms = $pendingAttendanceClassrooms ?? collect();
        $recentAttendanceSessions = $recentAttendanceSessions ?? collect();

        $card = "rounded-[28px] border border-black/5 bg-white/70 backdrop-blur-2xl shadow-[0_18px_45px_-35px_rgba(0,0,0,.45)]";
        $chip = "inline-flex items-center rounded-full border border-black/10 bg-white px-3 py-1 text-xs font-semibold text-slate-700";
        $mini = "rounded-2xl border border-black/5 bg-white/60 p-4";
    @endphp

    {{-- HERO --}}
    <div class="{{ $card }} p-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div>
                <div class="text-2xl md:text-3xl font-semibold text-slate-900">Bienvenue 👋</div>
                <div class="mt-1 text-sm text-slate-500">
                    Vue rapide : cours, évaluations, notes, absences — tout est visible par la direction automatiquement.
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <span class="{{ $chip }}">Semaine : {{ $startOfWeek->format('d/m/Y') }} → {{ $endOfWeek->format('d/m/Y') }}</span>
                    <span class="{{ $chip }}">Objectif : régularité + suivi clair</span>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('teacher.courses.create') }}"
                   class="rounded-2xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-black">
                    + Nouveau cours
                </a>

                <a href="{{ route('teacher.assessments.create') }}"
                   class="rounded-2xl border border-black/10 bg-white px-5 py-2.5 text-sm font-semibold hover:bg-slate-50">
                    + Nouvelle évaluation
                </a>

                <a href="{{ route('teacher.grades.index') }}"
                   class="rounded-2xl border border-black/10 bg-white px-5 py-2.5 text-sm font-semibold hover:bg-slate-50">
                    Saisir des notes →
                </a>

                <a href="{{ route('teacher.attendance.index') }}"
                   class="rounded-2xl border border-black/10 bg-white px-5 py-2.5 text-sm font-semibold hover:bg-slate-50">
                    Absences →
                </a>
            </div>
        </div>
    </div>

    {{-- KPIs --}}
    <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4">
        <div class="{{ $card }} p-5">
            <div class="text-xs uppercase tracking-wider text-slate-500">Cours (total)</div>
            <div class="mt-2 text-3xl font-semibold text-slate-900">{{ $kpis['courses_total'] }}</div>
            <div class="mt-2 text-xs text-slate-500">Depuis le début</div>
        </div>

        <div class="{{ $card }} p-5">
            <div class="text-xs uppercase tracking-wider text-slate-500">Cours (semaine)</div>
            <div class="mt-2 text-3xl font-semibold text-slate-900">{{ $kpis['courses_week'] }}</div>
            <div class="mt-2 text-xs text-slate-500">Cette semaine</div>
        </div>

        <div class="{{ $card }} p-5">
            <div class="text-xs uppercase tracking-wider text-slate-500">Évaluations (semaine)</div>
            <div class="mt-2 text-3xl font-semibold text-slate-900">{{ $kpis['assessments_week'] }}</div>
            <div class="mt-2 text-xs text-slate-500">Contrôles / examens</div>
        </div>

        <div class="{{ $card }} p-5">
            <div class="text-xs uppercase tracking-wider text-slate-500">Notes saisies (semaine)</div>
            <div class="mt-2 text-3xl font-semibold text-slate-900">{{ $kpis['grades_week'] }}</div>
            <div class="mt-2 text-xs text-slate-500">Toutes matières</div>
        </div>

        <div class="{{ $card }} p-5">
            <div class="text-xs uppercase tracking-wider text-slate-500">Absences (semaine)</div>
            <div class="mt-2 text-3xl font-semibold text-slate-900">{{ $kpis['attendance_week'] }}</div>
            <div class="mt-2 text-xs text-slate-500">Présent / absent / retard</div>
        </div>
    </div>

    {{-- GRID 3 blocs --}}
    <div class="mt-4 grid grid-cols-1 xl:grid-cols-3 gap-4">

        {{-- ALERTES --}}
        <div class="{{ $card }} p-6">
            <div class="text-sm font-semibold text-slate-900">Smart Alerts</div>
            <div class="mt-1 text-xs text-slate-500">Détection automatique (hebdo)</div>

            <div class="mt-4 space-y-3">
                <div class="{{ $mini }}">
                    <div class="text-xs font-semibold text-slate-500">Classes sans cours (semaine)</div>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @forelse($alerts['classrooms_no_courses'] as $c)
                            <span class="rounded-full bg-amber-50 text-amber-700 border border-amber-200 px-3 py-1 text-xs font-semibold">
                                {{ $c->name ?? 'Classe' }}
                            </span>
                        @empty
                            <div class="text-sm text-slate-600">✅ Toutes les classes ont au moins 1 cours</div>
                        @endforelse
                    </div>
                </div>

                <div class="{{ $mini }}">
                    <div class="text-xs font-semibold text-slate-500">Classes sans notes (semaine)</div>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @forelse($alerts['classrooms_no_grades'] as $c)
                            <span class="rounded-full bg-red-50 text-red-700 border border-red-200 px-3 py-1 text-xs font-semibold">
                                {{ $c->name ?? 'Classe' }}
                            </span>
                        @empty
                            <div class="text-sm text-slate-600">✅ Notes saisies pour toutes les classes</div>
                        @endforelse
                    </div>
                </div>

                <div class="{{ $mini }}">
                    <div class="text-xs font-semibold text-slate-500">Conseil</div>
                    <div class="mt-2 text-sm text-slate-700">
                        {{ $alerts['hint'] ?? "Publiez au moins 1 cours + 1 évaluation par classe chaque semaine." }}
                    </div>
                </div>
            </div>
        </div>

        {{-- ANALYSES NOTES --}}
        <div class="{{ $card }} p-6">
            <div class="flex items-center justify-between">
                <div class="text-sm font-semibold text-slate-900">Analyse des notes</div>
                <div class="text-xs text-slate-500">Semaine</div>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="{{ $mini }}">
                    <div class="text-xs font-semibold text-slate-500">Moyenne</div>
                    <div class="mt-1 text-2xl font-semibold text-slate-900">
                        {{ $analysis['avg'] !== null ? number_format($analysis['avg'],2) : '—' }}
                    </div>
                </div>
                <div class="{{ $mini }}">
                    <div class="text-xs font-semibold text-slate-500">Min</div>
                    <div class="mt-1 text-2xl font-semibold text-slate-900">
                        {{ $analysis['min'] !== null ? number_format($analysis['min'],2) : '—' }}
                    </div>
                </div>
                <div class="{{ $mini }}">
                    <div class="text-xs font-semibold text-slate-500">Max</div>
                    <div class="mt-1 text-2xl font-semibold text-slate-900">
                        {{ $analysis['max'] !== null ? number_format($analysis['max'],2) : '—' }}
                    </div>
                </div>
            </div>

            <div class="mt-4 {{ $mini }}">
                <div class="text-xs font-semibold text-slate-500">Distribution (sur 20)</div>
                <div class="mt-3 space-y-2">
                    @foreach($analysis['distribution'] as $d)
                        @php
                            $v = (int)($d['value'] ?? 0);
                            $pct = min(100, $v * 10); // simple bar (juste visuel)
                        @endphp
                        <div>
                            <div class="flex items-center justify-between text-xs">
                                <div class="font-semibold text-slate-700">{{ $d['label'] }}</div>
                                <div class="text-slate-500">{{ $v }}</div>
                            </div>
                            <div class="mt-1 h-2 w-full rounded-full bg-slate-100 overflow-hidden">
                                <div class="h-2 rounded-full bg-slate-900" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- RANKING CLASSES --}}
        <div class="{{ $card }} p-6">
            <div class="flex items-center justify-between">
                <div class="text-sm font-semibold text-slate-900">Classes — activité</div>
                <div class="text-xs text-slate-500">Semaine</div>
            </div>

            <div class="mt-4">
                <div class="text-xs font-semibold text-slate-500">Top (les plus actives)</div>
                <div class="mt-2 space-y-2">
                    @forelse($ranking['top'] as $c)
                        <div class="flex items-center justify-between rounded-2xl border border-black/5 bg-white/60 px-4 py-3">
                            <div class="min-w-0">
                                <div class="truncate text-sm font-semibold text-slate-900">{{ $c['name'] ?? 'Classe' }}</div>
                                <div class="text-xs text-slate-500">Cours: {{ $c['courses'] ?? 0 }} • Notes: {{ $c['grades'] ?? 0 }}</div>
                            </div>
                            <span class="rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 px-3 py-1 text-xs font-semibold">
                                {{ $c['score'] ?? 0 }} pts
                            </span>
                        </div>
                    @empty
                        <div class="text-sm text-slate-600">Aucune donnée.</div>
                    @endforelse
                </div>
            </div>

            <div class="mt-4">
                <div class="text-xs font-semibold text-slate-500">Low (à booster)</div>
                <div class="mt-2 space-y-2">
                    @forelse($ranking['low'] as $c)
                        <div class="flex items-center justify-between rounded-2xl border border-black/5 bg-white/60 px-4 py-3">
                            <div class="min-w-0">
                                <div class="truncate text-sm font-semibold text-slate-900">{{ $c['name'] ?? 'Classe' }}</div>
                                <div class="text-xs text-slate-500">Cours: {{ $c['courses'] ?? 0 }} • Notes: {{ $c['grades'] ?? 0 }}</div>
                            </div>
                            <span class="rounded-full bg-amber-50 text-amber-700 border border-amber-200 px-3 py-1 text-xs font-semibold">
                                {{ $c['score'] ?? 0 }} pts
                            </span>
                        </div>
                    @empty
                        <div class="text-sm text-slate-600">Aucune donnée.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4 grid grid-cols-1 xl:grid-cols-[360px_minmax(0,1fr)] gap-4">
        <div class="{{ $card }} p-6">
            <div class="flex items-center justify-between">
                <div class="text-sm font-semibold text-slate-900">Appel du jour</div>
                <a href="{{ route('teacher.attendance.index') }}" class="text-xs font-semibold text-slate-700 hover:underline">
                    Ouvrir le registre
                </a>
            </div>

            <div class="mt-4 {{ $mini }}">
                <div class="text-xs font-semibold text-slate-500">Classes en attente aujourd'hui</div>
                <div class="mt-3 space-y-2">
                    @forelse($pendingAttendanceClassrooms as $classroom)
                        <div class="flex items-center justify-between gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-3 py-3 text-sm">
                            <span class="font-semibold text-amber-900">{{ $classroom->name }}</span>
                            <span class="text-xs font-semibold uppercase tracking-[0.16em] text-amber-700">A faire</span>
                        </div>
                    @empty
                        <div class="text-sm text-slate-600">Tous vos appels du jour sont deja saisis.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="{{ $card }} p-6">
            <div class="flex items-center justify-between">
                <div class="text-sm font-semibold text-slate-900">Dernieres sessions d'appel</div>
                <div class="text-xs text-slate-500">Classe + date</div>
            </div>

            <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                @forelse($recentAttendanceSessions as $session)
                    <a href="{{ route('teacher.attendance.index', ['classroom_id' => $session['classroom_id'], 'date' => $session['date']->toDateString()]) }}"
                       class="rounded-2xl border border-black/5 bg-white/60 p-4 transition hover:bg-white">
                        <div class="text-sm font-semibold text-slate-900">{{ $session['classroom_name'] }}</div>
                        <div class="mt-1 text-xs text-slate-500">{{ $session['date']->format('d/m/Y') }}</div>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                                {{ $session['total_students'] }} eleves
                            </span>
                            <span class="rounded-full bg-red-50 text-red-700 border border-red-200 px-3 py-1 text-xs font-semibold">
                                {{ $session['absent_count'] }} absent(s)
                            </span>
                            <span class="rounded-full bg-amber-50 text-amber-700 border border-amber-200 px-3 py-1 text-xs font-semibold">
                                {{ $session['late_count'] }} retard(s)
                            </span>
                        </div>
                    </a>
                @empty
                    <div class="text-sm text-slate-600 md:col-span-2 xl:col-span-3">Aucune session d'appel recente.</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- FEED --}}
    <div class="mt-6 grid grid-cols-1 xl:grid-cols-2 gap-4">

        {{-- Derniers cours --}}
        <div class="{{ $card }} overflow-hidden">
            <div class="flex items-center justify-between border-b border-black/5 bg-white/60 px-6 py-4">
                <div class="text-sm font-semibold text-slate-900">Derniers cours</div>
                <a href="{{ route('teacher.courses.index') }}" class="text-xs font-semibold text-slate-700 hover:underline">
                    Voir tout →
                </a>
            </div>
            <div class="p-6 space-y-3">
                @forelse($latest['courses'] as $it)
                    <div class="rounded-2xl border border-black/5 bg-white/60 p-4">
                        <div class="text-sm font-semibold text-slate-900">{{ $it['title'] ?? 'Cours' }}</div>
                        <div class="mt-1 text-xs text-slate-500">
                            {{ $it['date'] ?? '' }}
                            @if(!empty($it['classroom'])) • Classe : {{ $it['classroom'] }} @endif
                        </div>
                    </div>
                @empty
                    <div class="text-sm text-slate-600">Aucun cours récent.</div>
                @endforelse
            </div>
        </div>

        {{-- Dernières saisies --}}
        <div class="{{ $card }} overflow-hidden">
            <div class="flex items-center justify-between border-b border-black/5 bg-white/60 px-6 py-4">
                <div class="text-sm font-semibold text-slate-900">Dernières saisies</div>
                <div class="text-xs text-slate-500">Notes / absences</div>
            </div>

            <div class="p-6 space-y-4">
                <div class="rounded-2xl border border-black/5 bg-white/60 p-4">
                    <div class="text-xs font-semibold text-slate-500">Notes (récentes)</div>
                    <div class="mt-3 space-y-2">
                        @forelse($latest['grades'] as $g)
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-semibold text-slate-900">
                                        {{ $g['student'] ?? 'Élève' }}
                                        @if(!empty($g['subject'])) • {{ $g['subject'] }} @endif
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        {{ $g['date'] ?? '' }}
                                        @if(!empty($g['classroom'])) • {{ $g['classroom'] }} @endif
                                    </div>
                                </div>
                                <span class="rounded-full bg-slate-900 text-white px-3 py-1 text-xs font-semibold">
                                    {{ $g['score'] ?? '—' }}
                                </span>
                            </div>
                        @empty
                            <div class="text-sm text-slate-600">Aucune note récente.</div>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-2xl border border-black/5 bg-white/60 p-4">
                    <div class="text-xs font-semibold text-slate-500">Absences (récentes)</div>
                    <div class="mt-3 space-y-2">
                        @forelse($latest['attendances'] as $a)
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-semibold text-slate-900">{{ $a['student'] ?? 'Élève' }}</div>
                                    <div class="text-xs text-slate-500">
                                        {{ $a['date'] ?? '' }}
                                        @if(!empty($a['classroom'])) • {{ $a['classroom'] }} @endif
                                    </div>
                                </div>

                                @php $st = strtolower((string)($a['status'] ?? '')); @endphp
                                @if($st === 'absent')
                                    <span class="rounded-full bg-red-50 text-red-700 border border-red-200 px-3 py-1 text-xs font-semibold">Absent</span>
                                @elseif($st === 'late' || $st === 'retard')
                                    <span class="rounded-full bg-amber-50 text-amber-700 border border-amber-200 px-3 py-1 text-xs font-semibold">En retard</span>
                                @else
                                    <span class="rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 px-3 py-1 text-xs font-semibold">Présent</span>
                                @endif
                            </div>
                        @empty
                            <div class="text-sm text-slate-600">Aucune absence récente.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="mt-4 {{ $card }} p-6">
        <div class="flex items-center justify-between gap-3">
            <div>
                <div class="text-sm font-semibold text-slate-900">Actualites agenda</div>
                <div class="mt-1 text-xs text-slate-500">Annonces publiees a partir des nouveaux evenements agenda.</div>
            </div>
            @if(Route::has('teacher.events.index'))
                <a href="{{ route('teacher.events.index') }}" class="text-xs font-semibold text-slate-700 hover:underline">
                    Voir agenda →
                </a>
            @endif
        </div>
        <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            @forelse(($latestAnnouncements ?? collect()) as $announcement)
                <article class="rounded-2xl border border-black/5 bg-white/60 p-4">
                    <p class="text-sm font-semibold text-slate-900">{{ $announcement->title }}</p>
                    <p class="mt-1 text-xs text-slate-500">
                        {{ $announcement->date?->format('d/m/Y') ?? '-' }}
                        <span class="mx-1 text-slate-300">|</span>
                        {{ $announcement->scope === 'classroom' ? 'Classe ciblee' : 'Toute l ecole' }}
                    </p>
                </article>
            @empty
                <div class="text-sm text-slate-600 md:col-span-2 xl:col-span-3">Aucune actualite publiee.</div>
            @endforelse
        </div>
    </div>
</x-teacher-layout>
