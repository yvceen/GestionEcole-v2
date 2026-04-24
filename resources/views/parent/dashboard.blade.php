<x-parent-layout
    title="Tableau de bord"
    subtitle="Visualisez rapidement les enfants relies a votre compte, leurs prochaines echeances et les indicateurs importants de la semaine."
>
    <section class="grid gap-4 xl:grid-cols-[minmax(0,1.45fr)_340px]">
        <div class="space-y-4">
            <article class="student-panel">
                <div class="flex flex-col gap-5 md:flex-row md:items-start md:justify-between">
                    <div>
                        <p class="student-eyebrow">Profil parent</p>
                        <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">{{ auth()->user()?->name }}</h2>
                        <p class="mt-2 text-sm text-slate-600">
                            {{ $children->count() }} enfant(s) rattache(s) a votre compte
                            @if($children->isNotEmpty())
                                <span class="mx-2 text-slate-300">|</span>
                                {{ $children->pluck('classroom.name')->filter()->unique()->implode(', ') }}
                            @endif
                        </p>
                    </div>

                    <div class="student-mini-panel max-w-xs">
                        <p class="student-mini-label">Notifications</p>
                        <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">{{ $unreadNotifications }}</p>
                        <p class="mt-1 text-sm text-slate-600">alerte(s) non lue(s)</p>
                    </div>
                </div>
            </article>

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                <article class="student-kpi">
                    <p class="student-kpi-label">Enfants</p>
                    <p class="student-kpi-value">{{ $children->count() }}</p>
                    <p class="student-kpi-copy">Associes a votre compte parent.</p>
                </article>

                <article class="student-kpi">
                    <p class="student-kpi-label">Cours visibles</p>
                    <p class="student-kpi-value">{{ $courses->count() }}</p>
                    <p class="student-kpi-copy">Cours publies pour les classes de vos enfants.</p>
                </article>

                <article class="student-kpi">
                    <p class="student-kpi-label">Devoirs a venir</p>
                    <p class="student-kpi-value">{{ $homeworks->count() }}</p>
                    <p class="student-kpi-copy">Echeances proches a surveiller.</p>
                </article>

                <article class="student-kpi">
                    <p class="student-kpi-label">Paiements</p>
                    <p class="student-kpi-value">{{ $paymentsCount }}</p>
                    <p class="student-kpi-copy">{{ number_format($paymentsTotal, 2) }} MAD au total.</p>
                </article>

                <article class="student-kpi">
                    <p class="student-kpi-label">Alertes presence</p>
                    <p class="student-kpi-value">{{ (int) ($attendanceNotificationCount ?? 0) }}</p>
                    <p class="student-kpi-copy">Notifications non lues sur absences et retards.</p>
                </article>
            </section>

            <section class="grid gap-4 lg:grid-cols-2">
                <article class="student-panel">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="student-panel-title">Prochain cours</p>
                            <p class="student-panel-copy">Le prochain horaire connu pour l un de vos enfants.</p>
                        </div>
                        <a href="{{ route('parent.children.index') }}" data-loading-label="Ouverture des enfants..." class="text-sm font-semibold text-sky-700 hover:text-sky-800">
                            Voir mes enfants
                        </a>
                    </div>

                    @if($nextClass)
                        <div class="mt-5 rounded-3xl border border-slate-200 bg-slate-50 px-5 py-5">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ $nextClass['day'] }}</p>
                            <h3 class="mt-2 text-xl font-semibold text-slate-950">{{ $nextClass['subject'] }}</h3>
                            <div class="mt-3 space-y-1 text-sm text-slate-600">
                                <p>Enfant : <span class="font-semibold text-slate-950">{{ $nextClass['child'] ?? '-' }}</span></p>
                                <p>Classe : <span class="font-semibold text-slate-950">{{ $nextClass['classroom'] ?? '-' }}</span></p>
                                <p>Horaire : <span class="font-semibold text-slate-950">{{ $nextClass['time'] }}</span></p>
                                <p>Enseignant : <span class="font-semibold text-slate-950">{{ $nextClass['teacher'] ?? '-' }}</span></p>
                            </div>
                        </div>
                    @else
                        <div class="student-empty mt-5">
                            Aucun cours a venir n est configure pour le moment.
                        </div>
                    @endif
                </article>

                <article class="student-panel">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="student-panel-title">Devoirs imminents</p>
                            <p class="student-panel-copy">Priorite aux devoirs avec echeance proche.</p>
                        </div>
                        <a href="{{ route('parent.homeworks.index') }}" data-loading-label="Ouverture des devoirs..." class="text-sm font-semibold text-sky-700 hover:text-sky-800">
                            Voir tous les devoirs
                        </a>
                    </div>

                    <div class="mt-5 space-y-3">
                        @forelse($homeworks as $homework)
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="font-semibold text-slate-950">{{ $homework->title }}</p>
                                        <p class="mt-1 text-xs text-slate-500">
                                            {{ $homework->classroom?->name ?? '-' }}
                                            @if($homework->teacher?->name)
                                                <span class="mx-1 text-slate-300">|</span>{{ $homework->teacher->name }}
                                            @endif
                                        </p>
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
                        <p class="student-panel-title">Absences et retards recents</p>
                        <p class="student-panel-copy">Dernieres alertes de presence pour vos enfants.</p>
                    </div>
                    <a href="{{ route('parent.attendance.index') }}" data-loading-label="Ouverture des presences..." class="text-sm font-semibold text-sky-700 hover:text-sky-800">
                        Voir tout le suivi
                    </a>
                </div>

                <div class="mt-5 space-y-3">
                    @forelse($recentAttendanceAlerts as $attendanceAlert)
                        @php
                            $tone = $attendanceAlert->status === 'absent'
                                ? 'border-rose-200 bg-rose-50 text-rose-700'
                                : 'border-amber-200 bg-amber-50 text-amber-700';
                        @endphp
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                <div class="min-w-0">
                                    <p class="font-semibold text-slate-950">{{ $attendanceAlert->student?->full_name ?? '-' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $attendanceAlert->classroom?->name ?? '-' }}
                                        <span class="mx-2 text-slate-300">|</span>
                                        {{ $attendanceAlert->date?->format('d/m/Y') ?? '-' }}
                                        @if($attendanceAlert->markedBy?->name)
                                            <span class="mx-2 text-slate-300">|</span>{{ $attendanceAlert->markedBy->name }}
                                        @endif
                                    </p>
                                    @if($attendanceAlert->note)
                                        <p class="mt-2 text-sm text-slate-600">{{ $attendanceAlert->note }}</p>
                                    @endif
                                </div>
                                <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold {{ $tone }}">
                                    {{ $attendanceAlert->status === 'late' ? 'En retard' : 'Absent' }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="student-empty">
                            Aucune absence ni retard recent a signaler.
                        </div>
                    @endforelse
                </div>
            </article>

            <article class="student-panel">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="student-panel-title">Actualites de l agenda</p>
                        <p class="student-panel-copy">Nouveaux evenements et mises a jour importantes de l ecole.</p>
                    </div>
                    @if(Route::has('parent.events.index'))
                        <a href="{{ route('parent.events.index') }}" data-loading-label="Ouverture de l'agenda..." class="text-sm font-semibold text-sky-700 hover:text-sky-800">
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
                        <p class="student-panel-copy">Les actions les plus utiles pour votre suivi quotidien.</p>
                    </div>
                    <span class="portal-chip">1 a 2 clics max</span>
                </div>
                <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                    <a href="{{ route('parent.children.index') }}" data-loading-label="Ouverture des enfants..." class="app-button-soft justify-start rounded-2xl px-4 py-3 text-sm font-semibold text-slate-900">
                        Voir mes enfants
                    </a>
                    <a href="{{ route('parent.grades.index') }}" data-loading-label="Ouverture des notes..." class="app-button-soft justify-start rounded-2xl px-4 py-3 text-sm font-semibold text-slate-900">
                        Consulter les notes
                    </a>
                    <a href="{{ route('parent.attendance.index') }}" data-loading-label="Ouverture des presences..." class="app-button-soft justify-start rounded-2xl px-4 py-3 text-sm font-semibold text-slate-900">
                        Suivre les presences
                    </a>
                    @if(Route::has('parent.pickup-requests.index'))
                        <a href="{{ route('parent.pickup-requests.index') }}" data-loading-label="Ouverture des demandes..." class="app-button-soft justify-start rounded-2xl px-4 py-3 text-sm font-semibold text-slate-900">
                            Demande de recuperation
                        </a>
                    @endif
                    <a href="{{ route('parent.finance.index') }}" data-loading-label="Ouverture de la finance..." class="app-button-soft justify-start rounded-2xl px-4 py-3 text-sm font-semibold text-slate-900">
                        Voir les paiements
                    </a>
                </div>
            </article>

            <article class="student-panel">
                <div>
                    <p class="student-panel-title">Derniers recus</p>
                    <p class="student-panel-copy">Acces direct aux paiements et justificatifs les plus recents.</p>
                </div>
                <div class="mt-4 space-y-3">
                    @forelse($receipts as $receipt)
                        <a href="{{ route('parent.finance.receipts.show', $receipt) }}" data-loading-label="Ouverture du recu..." class="block rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 transition hover:border-slate-300 hover:bg-white">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-slate-950">{{ $receipt->receipt_number }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $receipt->issued_at?->format('d/m/Y H:i') ?? '-' }}</p>
                                </div>
                                <span class="text-sm font-semibold text-slate-950">{{ number_format((float) $receipt->payments->sum('amount'), 2) }} MAD</span>
                            </div>
                        </a>
                    @empty
                        <div class="student-empty px-4 py-6">
                            Aucun recu disponible pour le moment.
                        </div>
                    @endforelse
                </div>
            </article>
        </aside>
    </section>
</x-parent-layout>
