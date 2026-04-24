<x-student-layout
    title="Tableau de bord"
    subtitle="Retrouvez en un coup d'oeil votre prochain cours, vos devoirs urgents, vos notifications et les indicateurs utiles de votre semaine."
>
    <section class="grid gap-4 xl:grid-cols-[minmax(0,1.4fr)_340px]">
        <div class="space-y-4">
            <article class="student-panel">
                <div class="flex flex-col gap-5 md:flex-row md:items-start md:justify-between">
                    <div>
                        <p class="student-eyebrow">Profil eleve</p>
                        <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">{{ $student->full_name }}</h2>
                        <p class="mt-2 text-sm text-slate-600">
                            Classe <span class="font-semibold text-slate-950">{{ $student->classroom?->name ?? '-' }}</span>
                            @if($student->classroom?->level?->name)
                                <span class="mx-2 text-slate-300">|</span>
                                Niveau <span class="font-semibold text-slate-950">{{ $student->classroom->level->name }}</span>
                            @endif
                        </p>
                        @if($student->parentUser?->name)
                            <p class="mt-2 text-sm text-slate-500">Parent referent : {{ $student->parentUser->name }}</p>
                        @endif
                    </div>

                    <div class="student-mini-panel max-w-xs">
                        <p class="student-mini-label">Alertes a lire</p>
                        <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">{{ $unreadNotifications }}</p>
                        <p class="mt-1 text-sm text-slate-600">notification(s) non lue(s)</p>
                    </div>
                </div>
            </article>

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <article class="student-kpi">
                    <p class="student-kpi-label">Cours visibles</p>
                    <p class="student-kpi-value">{{ $coursesCount }}</p>
                    <p class="student-kpi-copy">Cours publies pour votre classe.</p>
                </article>

                <article class="student-kpi">
                    <p class="student-kpi-label">Devoirs visibles</p>
                    <p class="student-kpi-value">{{ $homeworksCount }}</p>
                    <p class="student-kpi-copy">Devoirs approuves ou confirmes.</p>
                </article>

                <article class="student-kpi">
                    <p class="student-kpi-label">Absences</p>
                    <p class="student-kpi-value">{{ $absenceCount }}</p>
                    <p class="student-kpi-copy">Occurrences marquees absentes.</p>
                </article>

                <article class="student-kpi">
                    <p class="student-kpi-label">Alertes</p>
                    <p class="student-kpi-value">{{ $unreadNotifications }}</p>
                    <p class="student-kpi-copy">Notifications a consulter.</p>
                </article>
            </section>

            <section class="grid gap-4 lg:grid-cols-2">
                <article class="student-panel">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="student-panel-title">Prochain cours</p>
                            <p class="student-panel-copy">Base sur l'emploi du temps de votre classe.</p>
                        </div>
                        <a href="{{ route('student.timetable.index') }}" data-loading-label="Ouverture de l'emploi du temps..." class="text-sm font-semibold text-sky-700 hover:text-sky-800">
                            Voir l'emploi du temps
                        </a>
                    </div>

                    @if($nextClass)
                        <div class="mt-5 rounded-3xl border border-slate-200 bg-slate-50 px-5 py-5">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ $nextClass['day'] }}</p>
                            <h3 class="mt-2 text-xl font-semibold text-slate-950">{{ $nextClass['subject'] }}</h3>
                            <div class="mt-3 space-y-1 text-sm text-slate-600">
                                <p>Horaire : <span class="font-semibold text-slate-950">{{ $nextClass['time'] }}</span></p>
                                <p>Enseignant : <span class="font-semibold text-slate-950">{{ $nextClass['teacher'] ?? '-' }}</span></p>
                                <p>Salle : <span class="font-semibold text-slate-950">{{ $nextClass['room'] ?: '-' }}</span></p>
                            </div>
                        </div>
                    @else
                        <div class="student-empty mt-5">
                            Aucun cours a venir n'est configure pour votre classe.
                        </div>
                    @endif
                </article>

                <article class="student-panel">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="student-panel-title">Devoirs a venir</p>
                            <p class="student-panel-copy">Priorite aux devoirs avec echeance proche.</p>
                        </div>
                        <a href="{{ route('student.homeworks.index') }}" data-loading-label="Ouverture des devoirs..." class="text-sm font-semibold text-sky-700 hover:text-sky-800">
                            Voir tous les devoirs
                        </a>
                    </div>

                    <div class="mt-5 space-y-3">
                        @forelse($upcomingHomework as $homework)
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="font-semibold text-slate-950">{{ $homework->title }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ $homework->teacher?->name ?? 'Enseignant non renseigne' }}</p>
                                    </div>
                                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">
                                        {{ $homework->due_at?->format('d/m H:i') ?? '-' }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="student-empty">
                                Aucun devoir imminent a afficher.
                            </div>
                        @endforelse
                    </div>
                </article>
            </section>

            <article class="student-panel">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="student-panel-title">Actualites de l agenda</p>
                        <p class="student-panel-copy">Informations recentes sur les cours, examens et activites.</p>
                    </div>
                    @if(Route::has('student.events.index'))
                        <a href="{{ route('student.events.index') }}" data-loading-label="Ouverture de l'agenda..." class="text-sm font-semibold text-sky-700 hover:text-sky-800">
                            Ouvrir l agenda
                        </a>
                    @endif
                </div>
                <div class="mt-5 space-y-3">
                    @forelse(($latestAnnouncements ?? collect()) as $announcement)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                            <p class="font-semibold text-slate-950">{{ $announcement->title }}</p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ $announcement->date?->format('d/m/Y') ?? '-' }}
                                <span class="mx-2 text-slate-300">|</span>
                                {{ $announcement->scope === 'classroom' ? 'Classe ciblee' : 'Toute l ecole' }}
                            </p>
                        </div>
                    @empty
                        <div class="student-empty">Aucune actualite pour le moment.</div>
                    @endforelse
                </div>
            </article>
        </div>

        <aside class="order-first space-y-4 xl:order-none">
            <article class="student-panel">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="student-panel-title">Acces rapides</p>
                        <p class="student-panel-copy">Les rubriques essentielles restent visibles sans chercher dans tout le menu.</p>
                    </div>
                    <span class="portal-chip">Acces direct</span>
                </div>
                <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                    @if(Route::has('student.timetable.index'))
                        <a href="{{ route('student.timetable.index') }}" data-loading-label="Ouverture de l'emploi du temps..." class="app-button-soft justify-start rounded-2xl px-4 py-3 text-sm font-semibold text-slate-900">
                            Voir mon horaire
                        </a>
                    @endif
                    <a href="{{ route('student.grades.index') }}" data-loading-label="Ouverture des notes..." class="app-button-soft justify-start rounded-2xl px-4 py-3 text-sm font-semibold text-slate-900">
                        Consulter mes notes
                    </a>
                    <a href="{{ route('student.attendance.index') }}" data-loading-label="Ouverture des presences..." class="app-button-soft justify-start rounded-2xl px-4 py-3 text-sm font-semibold text-slate-900">
                        Voir mes presences
                    </a>
                    <a href="{{ route('student.notifications.index') }}" data-loading-label="Ouverture des notifications..." class="app-button-soft justify-start rounded-2xl px-4 py-3 text-sm font-semibold text-slate-900">
                        Ouvrir mes notifications
                    </a>
                </div>
            </article>

            <article class="student-panel">
                <div>
                    <p class="student-panel-title">Resume</p>
                    <p class="student-panel-copy">Vos informations scolaires importantes, regroupees dans un seul bloc.</p>
                </div>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500">Classe</dt>
                        <dd class="font-semibold text-slate-950">{{ $student->classroom?->name ?? '-' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500">Niveau</dt>
                        <dd class="font-semibold text-slate-950">{{ $student->classroom?->level?->name ?? '-' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500">Parent</dt>
                        <dd class="font-semibold text-slate-950">{{ $student->parentUser?->name ?? '-' }}</dd>
                    </div>
                </dl>
            </article>
        </aside>
    </section>
</x-student-layout>
