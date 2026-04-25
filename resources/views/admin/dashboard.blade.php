<x-admin-layout title="Tableau de bord">
    @php
        $structureStats = [
            [
                'title' => 'Eleves',
                'value' => $studentsCount,
                'meta' => 'Effectif total enregistre.',
                'badge' => 'Population scolaire',
                'tone' => 'blue',
                'icon' => 'students',
            ],
            [
                'title' => 'Classes',
                'value' => $classroomsCount,
                'meta' => 'Structure actuellement active.',
                'badge' => 'Organisation',
                'tone' => 'indigo',
                'icon' => 'classrooms',
            ],
            [
                'title' => 'Utilisateurs',
                'value' => $usersCount,
                'meta' => 'Parents et equipe connectes a la plateforme.',
                'badge' => 'Comptes actifs',
                'tone' => 'slate',
                'icon' => 'users',
                'chips' => [
                    ['label' => 'Parents', 'value' => $parentsCount, 'variant' => 'info'],
                    ['label' => 'Enseignants', 'value' => $teachersCount, 'variant' => 'info'],
                ],
            ],
            [
                'title' => 'Enseignants',
                'value' => $teachersCount,
                'meta' => 'Corps enseignant de l'etablissement.',
                'badge' => 'Pedagogie',
                'tone' => 'violet',
                'icon' => 'teachers',
            ],
        ];

        $attendanceCards = [
            [
                'title' => 'Presents',
                'value' => (int) ($attendanceSummary['today_present'] ?? 0),
                'meta' => 'Eleves pointes presents aujourd hui.',
                'badge' => 'Situation stable',
                'valueClass' => 'text-emerald-700',
                'ring' => 'from-emerald-500/15 via-emerald-500/5 to-white',
                'iconBg' => 'bg-emerald-500/12 text-emerald-700',
                'icon' => 'check',
            ],
            [
                'title' => 'Absents',
                'value' => (int) ($attendanceSummary['today_absent'] ?? 0),
                'meta' => 'Absences remontees aujourd hui dans l ecole active.',
                'badge' => 'Suivi necessaire',
                'valueClass' => 'text-rose-700',
                'ring' => 'from-rose-500/15 via-rose-500/5 to-white',
                'iconBg' => 'bg-rose-500/12 text-rose-700',
                'icon' => 'close',
            ],
            [
                'title' => 'Retards',
                'value' => (int) ($attendanceSummary['today_late'] ?? 0),
                'meta' => 'Retards saisis par les enseignants sur la journee.',
                'badge' => 'A verifier',
                'valueClass' => 'text-amber-700',
                'ring' => 'from-amber-500/15 via-amber-500/5 to-white',
                'iconBg' => 'bg-amber-500/12 text-amber-700',
                'icon' => 'clock',
            ],
        ];

        $overviewBadges = [
            ['label' => 'Structure', 'value' => $classroomsCount . ' classes'],
            ['label' => 'Communaute', 'value' => $usersCount . ' utilisateurs'],
            ['label' => 'Suivi', 'value' => (int) ($attendanceSummary['today_absent'] ?? 0) . ' absences du jour'],
        ];
    @endphp

    <x-ui.page-header
        title="Vue d'ensemble"
        subtitle="Suivez les indicateurs cles de l'etablissement et accedez rapidement aux actions les plus utilisees."
    >
        <x-slot name="actions">
            <x-ui.button :href="route('admin.students.create')" variant="primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-4 w-4" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
                </svg>
                Nouvel eleve
            </x-ui.button>

            <x-ui.button :href="route('admin.finance.payments.create')" variant="secondary">
                Ajouter un paiement
            </x-ui.button>

            <x-ui.button :href="route('admin.structure.index')" variant="ghost">
                Structure
            </x-ui.button>

            <x-ui.button :href="route('admin.users.index')" variant="ghost">
                Utilisateurs
            </x-ui.button>
        </x-slot>
    </x-ui.page-header>

    @if(Route::has('admin.homeworks.index') && (int) ($pendingHomeworks ?? 0) > 0)
        <x-ui.alert variant="warning">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="font-semibold">Devoirs en attente de traitement</div>
                    <div class="mt-1 text-sm">Vous avez actuellement {{ (int) ($pendingHomeworks ?? 0) }} devoir(s) a verifier.</div>
                </div>

                <x-ui.button :href="route('admin.homeworks.index', ['status' => 'pending'])" variant="outline" size="sm">
                    Voir les devoirs
                </x-ui.button>
            </div>
        </x-ui.alert>
    @endif

    <section class="relative overflow-hidden rounded-[32px] border border-slate-200/80 bg-gradient-to-br from-slate-50 via-white to-indigo-50/60 p-5 shadow-sm shadow-slate-200/70 sm:p-7">
        <div class="pointer-events-none absolute inset-x-0 top-0 h-32 bg-gradient-to-r from-sky-200/20 via-indigo-200/25 to-amber-200/15"></div>
        <div class="relative space-y-8">
            <div class="grid gap-5 xl:grid-cols-[minmax(0,1.35fr)_minmax(320px,0.85fr)]">
                <div class="rounded-[28px] border border-white/80 bg-white/90 p-6 shadow-[0_18px_50px_-32px_rgba(15,23,42,0.35)] backdrop-blur">
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                        <div class="max-w-2xl">
                            <span class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.22em] text-sky-700">
                                Pilotage quotidien
                            </span>
                            <h2 class="mt-4 text-2xl font-semibold tracking-tight text-slate-950 sm:text-[2rem]">
                                Un tableau de bord plus lisible pour suivre la structure, la finance et les presences.
                            </h2>
                            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600 sm:text-[15px]">
                                Retrouvez les indicateurs essentiels de l etablissement dans une composition plus claire, avec des reperes visuels rapides pour les priorites du jour.
                            </p>
                        </div>

                        <div class="grid gap-2 sm:grid-cols-3 lg:w-[320px] lg:grid-cols-1">
                            @foreach($overviewBadges as $badge)
                                <div class="rounded-2xl border border-slate-200/80 bg-slate-50/80 px-4 py-3">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">{{ $badge['label'] }}</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $badge['value'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="group relative overflow-hidden rounded-[30px] border border-amber-200/70 bg-gradient-to-br from-amber-50 via-white to-orange-50 p-6 shadow-[0_22px_60px_-34px_rgba(180,83,9,0.45)] transition duration-300 hover:-translate-y-1">
                    <div class="absolute right-0 top-0 h-36 w-36 rounded-full bg-amber-200/25 blur-3xl"></div>
                    <div class="relative flex h-full flex-col justify-between gap-6">
                        <div class="flex items-start justify-between gap-4">
                            <div class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-500/12 text-amber-700 ring-1 ring-inset ring-amber-200/80">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-6 w-6" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v18M7 7.5c0-1.38 2.24-2.5 5-2.5s5 1.12 5 2.5-2.24 2.5-5 2.5-5 1.12-5 2.5 2.24 2.5 5 2.5 5 1.12 5 2.5-2.24 2.5-5 2.5-5-1.12-5-2.5" />
                                </svg>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <x-ui.badge variant="warning">Finance</x-ui.badge>
                                <x-ui.badge variant="warning">MAD</x-ui.badge>
                            </div>
                        </div>

                        <div>
                            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-amber-700/80">Revenus du mois</p>
                            <p class="mt-3 text-4xl font-semibold tracking-tight text-slate-950">{{ number_format($revenueThisMonth, 2) }}</p>
                            <p class="mt-3 max-w-sm text-sm leading-6 text-slate-600">
                                Cliquez sur un mois dans le graphique pour afficher le detail des paiements et comparer rapidement les encaissements.
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                Focus financier
                            </span>
                            <span class="text-xs font-medium uppercase tracking-[0.16em] text-slate-500">Mise a jour dynamique via le graphique</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div class="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Indicateurs essentiels</p>
                        <h3 class="mt-1 text-xl font-semibold tracking-tight text-slate-950">Structure et activite de l etablissement</h3>
                    </div>
                    <p class="max-w-2xl text-sm leading-6 text-slate-500">
                        Une lecture rapide des volumes cles pour l organisation, les equipes et la base utilisateurs.
                    </p>
                </div>

                <div class="grid gap-4 md:grid-cols-2 2xl:grid-cols-4">
                    @foreach($structureStats as $stat)
                        <article class="group relative overflow-hidden rounded-[28px] border border-slate-200/80 bg-white p-5 shadow-[0_16px_45px_-34px_rgba(15,23,42,0.45)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_26px_60px_-36px_rgba(37,99,235,0.35)]">
                            <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r {{ $stat['tone'] === 'blue' ? 'from-sky-500 via-blue-500 to-indigo-500' : ($stat['tone'] === 'indigo' ? 'from-indigo-500 via-violet-500 to-sky-500' : ($stat['tone'] === 'violet' ? 'from-violet-500 via-fuchsia-500 to-indigo-500' : 'from-slate-400 via-slate-500 to-slate-600')) }}"></div>
                            <div class="flex items-start justify-between gap-4">
                                <div class="inline-flex h-12 w-12 items-center justify-center rounded-2xl ring-1 ring-inset {{ $stat['tone'] === 'blue' ? 'bg-sky-50 text-sky-700 ring-sky-200' : ($stat['tone'] === 'indigo' ? 'bg-indigo-50 text-indigo-700 ring-indigo-200' : ($stat['tone'] === 'violet' ? 'bg-violet-50 text-violet-700 ring-violet-200' : 'bg-slate-100 text-slate-700 ring-slate-200')) }}">
                                    @switch($stat['icon'])
                                        @case('students')
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-6 w-6" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 19a4 4 0 0 0-8 0" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 11a3 3 0 1 0-3-3 3 3 0 0 0 3 3Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 19a3 3 0 0 0-3-2.8M17 10.5a2.5 2.5 0 1 0-1.1-4.8M5 19a3 3 0 0 1 3-2.8M7 10.5A2.5 2.5 0 1 1 8.1 5.7" />
                                            </svg>
                                            @break
                                        @case('classrooms')
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-6 w-6" stroke-width="1.8">
                                                <rect x="3.5" y="5" width="17" height="13" rx="2" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 9h10M7 13h6M9 18v2m6-2v2" />
                                            </svg>
                                            @break
                                        @case('teachers')
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-6 w-6" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4 4 8l8 4 8-4-8-4Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 11.5V15c0 1.66 1.79 3 4 3s4-1.34 4-3v-3.5" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 10v6" />
                                            </svg>
                                            @break
                                        @default
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-6 w-6" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 19a4 4 0 0 0-8 0" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 11a3 3 0 1 0-3-3 3 3 0 0 0 3 3Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 19a3 3 0 0 0-3-2.8M17 10.5a2.5 2.5 0 1 0-1.1-4.8M5 19a3 3 0 0 1 3-2.8M7 10.5A2.5 2.5 0 1 1 8.1 5.7" />
                                            </svg>
                                    @endswitch
                                </div>
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-600">
                                    {{ $stat['badge'] }}
                                </span>
                            </div>

                            <div class="mt-6">
                                <p class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-500">{{ $stat['title'] }}</p>
                                <p class="mt-3 text-4xl font-semibold tracking-tight text-slate-950">{{ $stat['value'] }}</p>
                                <p class="mt-3 text-sm leading-6 text-slate-500">{{ $stat['meta'] }}</p>
                            </div>

                            @if(!empty($stat['chips']))
                                <div class="mt-5 flex flex-wrap gap-2">
                                    @foreach($stat['chips'] as $chip)
                                        <x-ui.badge :variant="$chip['variant']">{{ $chip['label'] }} : {{ $chip['value'] }}</x-ui.badge>
                                    @endforeach
                                </div>
                            @endif
                        </article>
                    @endforeach
                </div>
            </div>

            <div class="rounded-[30px] border border-slate-200/80 bg-white/90 p-5 shadow-[0_18px_50px_-34px_rgba(15,23,42,0.35)] sm:p-6">
                <div class="flex flex-col gap-2 border-b border-slate-200/80 pb-5 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Presences</p>
                        <h3 class="mt-1 text-xl font-semibold tracking-tight text-slate-950">Synthese du jour</h3>
                    </div>
                    <p class="max-w-2xl text-sm leading-6 text-slate-500">
                        Les valeurs ci-dessous mettent en avant les presences, absences et retards deja consolides pour la journee.
                    </p>
                </div>

                <div class="mt-5 grid gap-4 lg:grid-cols-3">
                    @foreach($attendanceCards as $card)
                        <article class="group relative overflow-hidden rounded-[26px] border border-slate-200/70 bg-gradient-to-br {{ $card['ring'] }} p-5 shadow-[0_14px_40px_-34px_rgba(15,23,42,0.45)] transition duration-300 hover:-translate-y-1">
                            <div class="flex items-start justify-between gap-4">
                                <div class="inline-flex h-11 w-11 items-center justify-center rounded-2xl ring-1 ring-inset ring-current/10 {{ $card['iconBg'] }}">
                                    @switch($card['icon'])
                                        @case('check')
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-5 w-5" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4.2 4.2L19 6.5" />
                                            </svg>
                                            @break
                                        @case('close')
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-5 w-5" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6 6 18" />
                                            </svg>
                                            @break
                                        @default
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-5 w-5" stroke-width="2">
                                                <circle cx="12" cy="12" r="8" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l2.5 2.5" />
                                            </svg>
                                    @endswitch
                                </div>
                                <span class="inline-flex items-center rounded-full bg-white/80 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-600 ring-1 ring-inset ring-slate-200/80">
                                    {{ $card['badge'] }}
                                </span>
                            </div>

                            <div class="mt-5">
                                <p class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-500">{{ $card['title'] }}</p>
                                <p class="mt-3 text-4xl font-semibold tracking-tight {{ $card['valueClass'] }}">{{ $card['value'] }}</p>
                                <p class="mt-3 text-sm leading-6 text-slate-500">{{ $card['meta'] }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <x-ui.card
        title="Vue hebdomadaire des presences"
        subtitle="Lecture rapide des absences et retards deja remontes cette semaine."
        class="border border-slate-200/80 bg-white/90 shadow-[0_18px_50px_-34px_rgba(15,23,42,0.35)]"
    >
        <div class="mb-5 flex flex-wrap items-center gap-2">
            <x-ui.badge variant="info">Absences</x-ui.badge>
            <x-ui.badge variant="warning">Retards</x-ui.badge>
            <span class="text-sm text-slate-500">Consultez les tendances de la semaine avant d ouvrir le suivi detaille.</span>
        </div>

        <div class="grid gap-3 md:grid-cols-7">
            @forelse(($attendanceSummary['weekly_overview'] ?? collect()) as $day)
                <div class="rounded-[24px] border border-slate-200/80 bg-gradient-to-br from-slate-50 to-white px-4 py-4 shadow-sm shadow-slate-200/60 transition duration-300 hover:-translate-y-1">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ $day['label'] }}</p>
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-slate-900 text-xs font-semibold text-white">
                            {{ \Illuminate\Support\Str::substr((string) $day['label'], 0, 1) }}
                        </span>
                    </div>
                    <div class="mt-3 space-y-2">
                        <div class="flex items-center justify-between rounded-2xl bg-rose-50 px-3 py-2 text-sm">
                            <span class="text-slate-600">Absences</span>
                            <span class="font-semibold text-rose-700">{{ $day['absent'] }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-2xl bg-amber-50 px-3 py-2 text-sm">
                            <span class="text-slate-600">Retards</span>
                            <span class="font-semibold text-amber-700">{{ $day['late'] }}</span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-[24px] border border-dashed border-slate-300 bg-slate-50/90 px-6 py-8 text-sm text-slate-500 md:col-span-7">
                    Aucune donnee d'appel pour la semaine en cours.
                </div>
            @endforelse
        </div>

        <div class="mt-4">
            <x-ui.button :href="route('admin.attendance.index')" variant="secondary" size="sm">
                Ouvrir le suivi des presences
            </x-ui.button>
        </div>
    </x-ui.card>

    <section class="grid gap-6 xl:grid-cols-[minmax(0,2fr)_380px]">
        <x-ui.card
            title="Revenus sur 12 mois"
            subtitle="Selectionnez un point pour consulter les paiements du mois correspondant."
            class="border border-slate-200/80 bg-white/90 shadow-[0_18px_50px_-34px_rgba(15,23,42,0.35)]"
        >
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <div class="h-[340px]">
                    <canvas id="revChart"></canvas>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <x-ui.badge variant="info">Revenus mensuels</x-ui.badge>
                @if(!empty($selected))
                    <x-ui.badge variant="success">Mois selectionne : {{ $selected }}</x-ui.badge>
                @else
                    <span class="app-hint">Astuce : cliquez sur un point du graphique pour afficher les paiements detailles.</span>
                @endif
            </div>
        </x-ui.card>

        <x-ui.card
            title="Detail du mois"
            :subtitle="!empty($selected) ? 'Mois : '.$selected : 'Selectionnez un mois dans le graphique.'"
            class="border border-slate-200/80 bg-white/90 shadow-[0_18px_50px_-34px_rgba(15,23,42,0.35)]"
        >
            <div class="overflow-hidden rounded-2xl border border-slate-200">
                <div class="max-h-[360px] overflow-auto">
                    <table class="app-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th class="text-right">Montant</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($monthPayments as $payment)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($payment->paid_at)->format('d/m/Y') }}</td>
                                    <td class="text-right font-semibold text-slate-900">{{ number_format((float) $payment->amount, 2) }} MAD</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="px-5 py-6 text-center text-sm text-slate-500">
                                        @if(!empty($selected))
                                            Aucun paiement enregistre pour ce mois.
                                        @else
                                            Selectionnez un mois pour afficher le detail.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4 flex items-center justify-between gap-3">
                <p class="app-hint">Le tableau se met a jour automatiquement apres selection d'un mois.</p>

                @if(!empty($selected))
                    <x-ui.button :href="route('admin.dashboard')" variant="secondary" size="sm">
                        Reinitialiser
                    </x-ui.button>
                @endif
            </div>
        </x-ui.card>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1"></script>

    <script>
        (function () {
            const labels = @json($chartLabels);
            const values = @json($chartValues);
            const monthKey = @json($chartKeys);

            const canvas = document.getElementById('revChart');
            const ctx = canvas.getContext('2d');

            function makeGradient() {
                const h = canvas.clientHeight || 340;
                const g = ctx.createLinearGradient(0, 0, 0, h);
                g.addColorStop(0, 'rgba(14, 116, 144, 0.22)');
                g.addColorStop(1, 'rgba(14, 116, 144, 0)');
                return g;
            }

            const selectedMonth = @json($selected);

            const chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                        data: values,
                        fill: true,
                        backgroundColor: makeGradient(),
                        borderColor: '#0f172a',
                        borderWidth: 3,
                        tension: 0.35,
                        pointRadius: (context) => {
                            const index = context.dataIndex;
                            return selectedMonth && monthKey[index] === selectedMonth ? 6 : 4;
                        },
                        pointHoverRadius: 8,
                        pointBorderWidth: 2,
                        pointBorderColor: '#0f172a',
                        pointBackgroundColor: '#ffffff',
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            displayColors: false,
                            padding: 12,
                            backgroundColor: 'rgba(15, 23, 42, 0.95)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            callbacks: {
                                title: (items) => items?.[0]?.label ?? '',
                                label: (tooltipItem) => {
                                    const value = tooltipItem.parsed.y ?? 0;
                                    return value.toFixed(2) + ' MAD';
                                }
                            }
                        }
                    },
                    interaction: { mode: 'nearest', intersect: false },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(15, 23, 42, 0.08)' },
                            ticks: { callback: (value) => value + ' MAD' }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { maxRotation: 35, minRotation: 35 }
                        }
                    },
                    onHover: (_, elements) => {
                        canvas.style.cursor = elements?.length ? 'pointer' : 'default';
                    },
                    onClick: (event) => {
                        const points = chart.getElementsAtEventForMode(event, 'nearest', { intersect: false }, true);
                        if (!points.length) {
                            return;
                        }

                        const index = points[0].index;
                        const ym = monthKey[index];
                        const url = new URL(window.location.href);
                        url.searchParams.set('month', ym);
                        window.location.href = url.toString();
                    }
                }
            });

            window.addEventListener('resize', () => {
                chart.data.datasets[0].backgroundColor = makeGradient();
                chart.update('none');
            });
        })();
    </script>
</x-admin-layout>
