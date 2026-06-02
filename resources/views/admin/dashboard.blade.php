<x-admin-layout title="Tableau de bord">
    @php
        $structureStats = [
            [
                'title' => 'Élèves',
                'value' => $studentsCount,
                'meta' => 'Effectif total enregistré.',
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
                'meta' => "Corps enseignant de l'Établissement.",
                'badge' => 'Pédagogie',
                'tone' => 'violet',
                'icon' => 'teachers',
            ],
        ];

        $attendanceCards = [
            [
                'title' => 'Presents',
                'value' => (int) ($attendanceSummary['today_present'] ?? 0),
                'meta' => 'Élèves pointes presents aujourd hui.',
                'badge' => 'Situation stable',
                'valueClass' => 'text-emerald-700',
                'ring' => 'from-emerald-500/15 via-emerald-500/5 to-white',
                'iconBg' => 'bg-emerald-500/12 text-emerald-700',
                'icon' => 'check',
            ],
            [
                'title' => 'Absents',
                'value' => (int) ($attendanceSummary['today_absent'] ?? 0),
                'meta' => 'Absences remontees aujourd hui dans l'École active.',
                'badge' => 'Suivi necessaire',
                'valueClass' => 'text-rose-700',
                'ring' => 'from-rose-500/15 via-rose-500/5 to-white',
                'iconBg' => 'bg-rose-500/12 text-rose-700',
                'icon' => 'close',
            ],
            [
                'title' => 'Retards',
                'value' => (int) ($attendanceSummary['today_late'] ?? 0),
                'meta' => 'Retards saisis par les enseignants sur la journée.',
                'badge' => 'A verifier',
                'valueClass' => 'text-amber-700',
                'ring' => 'from-amber-500/15 via-amber-500/5 to-white',
                'iconBg' => 'bg-amber-500/12 text-amber-700',
                'icon' => 'clock',
            ],
        ];

        $quickLinks = [
            ['label' => 'Nouvel Élève', 'href' => route('admin.students.create'), 'variant' => 'primary'],
            ['label' => 'Paiement', 'href' => route('admin.finance.payments.create'), 'variant' => 'secondary'],
            ['label' => 'Structure', 'href' => route('admin.structure.index'), 'variant' => 'secondary'],
            ['label' => 'Utilisateurs', 'href' => route('admin.users.index'), 'variant' => 'secondary'],
        ];

        $chartMax = collect($chartValues)->max() ?: 0;
        $chartBestIndex = collect($chartValues)->search($chartMax);
        $chartBestLabel = $chartBestIndex !== false ? ($chartLabels[$chartBestIndex] ?? '-') : '-';
        $chartAverage = count($chartValues) ? array_sum($chartValues) / count($chartValues) : 0;
        $selectedIndex = $selected ? array_search($selected, $chartKeys, true) : false;
        $selectedChartTotal = $selectedIndex !== false ? (float) ($chartValues[$selectedIndex] ?? 0) : $revenueThisMonth;
        $selectedChartCount = $selectedIndex !== false ? (int) ($chartCounts[$selectedIndex] ?? 0) : $financeCount;
    @endphp

    <x-ui.page-header
        title="Vue d'ensemble"
        subtitle="Les chiffres essentiels de l'Établissement, sans surcharge."
    >
        <x-slot name="actions">
            @foreach($quickLinks as $link)
                <x-ui.button :href="$link['href']" :variant="$link['variant']" size="sm">
                    {{ $link['label'] }}
                </x-ui.button>
            @endforeach
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

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach($structureStats as $stat)
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">{{ $stat['title'] }}</p>
                        <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">{{ $stat['value'] }}</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600">
                        {{ $stat['badge'] }}
                    </span>
                </div>

                <p class="mt-3 text-sm text-slate-500">{{ $stat['meta'] }}</p>

                @if(!empty($stat['chips']))
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach($stat['chips'] as $chip)
                            <x-ui.badge :variant="$chip['variant']">{{ $chip['label'] }} : {{ $chip['value'] }}</x-ui.badge>
                        @endforeach
                    </div>
                @endif
            </article>
        @endforeach
    </section>

    <section class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_320px]">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Présences</p>
                    <h2 class="mt-1 text-lg font-semibold text-slate-950">Aujourd'hui</h2>
                </div>
                <x-ui.button :href="route('admin.attendance.index')" variant="secondary" size="sm">
                    Suivi des présences
                </x-ui.button>
            </div>

            <div class="mt-4 grid gap-3 md:grid-cols-3">
                @foreach($attendanceCards as $card)
                    <article class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-semibold text-slate-700">{{ $card['title'] }}</p>
                            <span class="text-xs font-medium text-slate-500">{{ $card['badge'] }}</span>
                        </div>
                        <p class="mt-2 text-3xl font-semibold tracking-tight {{ $card['valueClass'] }}">{{ $card['value'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>

        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-amber-700">Finance</p>
            <h2 class="mt-1 text-lg font-semibold text-slate-950">Revenus du mois</h2>
            <p class="mt-4 text-3xl font-semibold tracking-tight text-slate-950">{{ number_format($revenueThisMonth, 2) }} MAD</p>
            <x-ui.button :href="route('admin.finance.index')" variant="secondary" size="sm" class="mt-4">
                Ouvrir la finance
            </x-ui.button>
        </div>
    </section>

    <x-ui.card
        title="Vue hebdomadaire des présences"
        subtitle="Absences et retards de la semaine."
        class="border border-slate-200 bg-white shadow-sm"
    >
        <div class="grid gap-3 md:grid-cols-7">
            @forelse(($attendanceSummary['weekly_overview'] ?? collect()) as $day)
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ $day['label'] ?? 'Jour' }}</p>
                    </div>
                    <div class="mt-3 space-y-2">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-600">Absences</span>
                            <span class="font-semibold text-rose-700">{{ $day['absent'] ?? 0 }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-600">Retards</span>
                            <span class="font-semibold text-amber-700">{{ $day['late'] ?? 0 }}</span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-[24px] border border-dashed border-slate-300 bg-slate-50/90 px-6 py-8 text-sm text-slate-500 md:col-span-7">
                    Aucune donnee d'appel pour la semaine en cours.
                </div>
            @endforelse
        </div>

    </x-ui.card>

    <section class="grid gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(520px,0.85fr)]">
        <x-ui.card
            title="Revenus sur 12 mois"
            subtitle="Lecture claire des encaissements mensuels, avec sélection rapide par mois."
            class="border border-slate-200 bg-white shadow-sm"
        >
            <div class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-2xl border border-sky-200 bg-gradient-to-br from-sky-50 to-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-sky-700">Sélection</p>
                    <p class="mt-1 text-xl font-semibold text-slate-950">{{ number_format($selectedChartTotal, 2) }} MAD</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $selectedChartCount }} paiement(s)</p>
                </div>
                <div class="rounded-2xl border border-emerald-200 bg-gradient-to-br from-emerald-50 to-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-emerald-700">Meilleur mois</p>
                    <p class="mt-1 text-xl font-semibold text-slate-950">{{ number_format($chartMax, 2) }} MAD</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $chartBestLabel }}</p>
                </div>
                <div class="rounded-2xl border border-violet-200 bg-gradient-to-br from-violet-50 to-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-violet-700">Moyenne</p>
                    <p class="mt-1 text-xl font-semibold text-slate-950">{{ number_format($chartAverage, 2) }} MAD</p>
                    <p class="mt-1 text-xs text-slate-500">Sur 12 mois</p>
                </div>
            </div>

            <div class="mt-4 rounded-[28px] border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-sky-50/80 p-5 shadow-inner">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-950">Encaissements mensuels</p>
                        <p class="mt-1 text-xs text-slate-500">Cliquez sur une barre pour filtrer les paiements du mois.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-2 rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700 ring-1 ring-sky-200">
                            <span class="h-2 w-2 rounded-full bg-sky-500"></span>Revenus
                        </span>
                        <span class="inline-flex items-center gap-2 rounded-full bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-600 ring-1 ring-slate-200">
                            <span class="h-2 w-2 rounded-full bg-slate-500"></span>Moyenne
                        </span>
                    </div>
                </div>
                <div class="h-[360px]">
                    <canvas id="revChart"></canvas>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-2">
                @if(!empty($selected))
                    <x-ui.badge variant="success">Mois sélectionné : {{ $selected }}</x-ui.badge>
                @else
                    <span class="app-hint">Astuce : cliquez sur une barre pour afficher les paiements detailles.</span>
                @endif
            </div>
        </x-ui.card>

        <x-ui.card
            title="Paiements detailles"
            subtitle="Filtrez par mois, date, méthode ou recherche."
            class="border border-slate-200 bg-white shadow-sm"
        >
            <form method="GET" action="{{ route('admin.dashboard') }}" class="grid gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:grid-cols-2">
                <div>
                    <label class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Mois</label>
                    <select name="month" class="mt-1 w-full rounded-xl border-slate-200 bg-white text-sm">
                        <option value="">Mois actuel</option>
                        @foreach($chartKeys as $index => $key)
                            <option value="{{ $key }}" @selected($selected === $key)>{{ $chartLabels[$index] ?? $key }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Méthode</label>
                    <select name="finance_method" class="mt-1 w-full rounded-xl border-slate-200 bg-white text-sm">
                        <option value="">Toutes</option>
                        @foreach($financeMethods as $method => $label)
                            <option value="{{ $method }}" @selected($financeMethod === $method)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Du</label>
                    <input type="date" name="finance_from" value="{{ $financeFrom }}" class="mt-1 w-full rounded-xl border-slate-200 bg-white text-sm">
                </div>

                <div>
                    <label class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Au</label>
                    <input type="date" name="finance_to" value="{{ $financeTo }}" class="mt-1 w-full rounded-xl border-slate-200 bg-white text-sm">
                </div>

                <div class="sm:col-span-2">
                    <label class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Recherche</label>
                    <input name="finance_search" value="{{ $financeSearch }}" class="mt-1 w-full rounded-xl border-slate-200 bg-white text-sm" placeholder="Élève, parent, reçu ou note...">
                </div>

                <div class="flex flex-wrap gap-2 sm:col-span-2">
                    <x-ui.button type="submit" variant="primary" size="sm">Filtrer</x-ui.button>
                    <x-ui.button :href="route('admin.dashboard')" variant="secondary" size="sm">Reinitialiser</x-ui.button>
                    <x-ui.button :href="route('admin.finance.index')" variant="secondary" size="sm">Finance complete</x-ui.button>
                </div>
            </form>

            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Total filtre</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-950">{{ number_format($financeTotal, 2) }} MAD</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Paiements</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-950">{{ $financeCount }}</p>
                </div>
            </div>

            <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200">
                <div class="max-h-[460px] overflow-auto">
                    <table class="app-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Élève</th>
                                <th>Parent</th>
                                <th>Reçu</th>
                                <th>Méthode</th>
                                <th class="text-right">Montant</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($monthPayments as $payment)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($payment->paid_at)->format('d/m/Y') }}</td>
                                    <td class="font-semibold text-slate-900">{{ $payment->student_name ?? '-' }}</td>
                                    <td>{{ $payment->parent_name ?? '-' }}</td>
                                    <td>{{ $payment->receipt_number ?? '-' }}</td>
                                    <td>{{ $financeMethods[$payment->method] ?? ucfirst((string) $payment->method) }}</td>
                                    <td class="text-right font-semibold text-slate-900">{{ number_format((float) $payment->amount, 2) }} MAD</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-6 text-center text-sm text-slate-500">
                                        Aucun paiement ne correspond aux filtres.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4 flex items-center justify-between gap-3">
                <p class="app-hint">Affichage limite aux 300 derniers paiements correspondant aux filtres.</p>
            </div>
        </x-ui.card>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1"></script>

    <script>
        (function () {
            const labels = @json($chartLabels);
            const values = @json($chartValues);
            const counts = @json($chartCounts);
            const monthKey = @json($chartKeys);

            const canvas = document.getElementById('revChart');
            const ctx = canvas.getContext('2d');

            const selectedMonth = @json($selected);
            const averageValue = @json((float) $chartAverage);
            const formatMoney = (value) => new Intl.NumberFormat('fr-FR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(Number(value || 0)) + ' MAD';

            function makeBarGradient() {
                const h = canvas.clientHeight || 340;
                const g = ctx.createLinearGradient(0, 0, 0, h);
                g.addColorStop(0, 'rgba(14, 165, 233, 0.95)');
                g.addColorStop(0.55, 'rgba(56, 189, 248, 0.62)');
                g.addColorStop(1, 'rgba(186, 230, 253, 0.42)');
                return g;
            }

            function barColors() {
                const max = Math.max(...values.map(Number), 1);
                const gradient = makeBarGradient();
                return values.map((value, index) => {
                    if (selectedMonth && monthKey[index] === selectedMonth) {
                        return 'rgba(2, 132, 199, 0.98)';
                    }

                    const ratio = Number(value || 0) / max;
                    if (ratio >= 0.75) {
                        return 'rgba(16, 185, 129, 0.78)';
                    }
                    if (ratio >= 0.35) {
                        return gradient;
                    }
                    return 'rgba(203, 213, 225, 0.65)';
                });
            }

            const chart = new Chart(ctx, {
                data: {
                    labels,
                    datasets: [
                        {
                            type: 'bar',
                            label: 'Revenus',
                            data: values,
                            backgroundColor: barColors(),
                            borderColor: (context) => {
                                const index = context.dataIndex;
                                return selectedMonth && monthKey[index] === selectedMonth
                                    ? 'rgba(2, 132, 199, 1)'
                                    : 'rgba(14, 165, 233, 0.16)';
                            },
                            borderWidth: 1,
                            borderRadius: 12,
                            borderSkipped: false,
                            maxBarThickness: 42,
                            yAxisID: 'y',
                        },
                        {
                            type: 'line',
                            label: 'Moyenne',
                            data: values.map(() => averageValue),
                            borderColor: 'rgba(15, 23, 42, 0.72)',
                            borderWidth: 2,
                            borderDash: [8, 7],
                            pointRadius: 0,
                            pointHoverRadius: 0,
                            yAxisID: 'y',
                        },
                        {
                            type: 'line',
                            label: 'Sélection',
                            data: values,
                            borderColor: 'rgba(15, 23, 42, 0.92)',
                            backgroundColor: '#ffffff',
                            borderWidth: 0,
                            showLine: false,
                            pointRadius: (context) => {
                                const index = context.dataIndex;
                                return selectedMonth && monthKey[index] === selectedMonth ? 6 : 0;
                            },
                            pointHoverRadius: 7,
                            pointBorderWidth: 3,
                            pointBorderColor: '#0f172a',
                            pointBackgroundColor: '#ffffff',
                            yAxisID: 'y',
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            enabled: true,
                            displayColors: false,
                            padding: 12,
                            cornerRadius: 12,
                            backgroundColor: 'rgba(15, 23, 42, 0.94)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            filter: (tooltipItem) => tooltipItem.dataset.label === 'Revenus',
                            callbacks: {
                                title: (items) => items?.[0]?.label ?? '',
                                label: (tooltipItem) => {
                                    const index = tooltipItem.dataIndex;
                                    return 'Revenus : ' + formatMoney(values[index] ?? 0);
                                },
                                afterBody: (items) => {
                                    const index = items?.[0]?.dataIndex ?? 0;
                                    return [
                                        'Paiements : ' + (counts[index] ?? 0),
                                        'Cliquez pour filtrer ce mois',
                                    ];
                                },
                            }
                        }
                    },
                    interaction: { mode: 'index', intersect: false },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grace: '12%',
                            grid: { color: 'rgba(15, 23, 42, 0.055)', drawTicks: false },
                            border: { display: false },
                            ticks: {
                                color: '#64748b',
                                padding: 8,
                                callback: (value) => new Intl.NumberFormat('fr-FR', {
                                    maximumFractionDigits: 0,
                                }).format(Number(value || 0)) + ' MAD'
                            }
                        },
                        x: {
                            grid: { display: false },
                            border: { display: false },
                            ticks: {
                                maxRotation: 0,
                                minRotation: 0,
                                color: '#64748b',
                                font: { size: 11, weight: '600' },
                                callback: function(value, index) {
                                    const label = this.getLabelForValue(value);
                                    return index % 2 === 0 ? label : '';
                                }
                            }
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
                        url.searchParams.delete('finance_from');
                        url.searchParams.delete('finance_to');
                        url.searchParams.delete('finance_method');
                        url.searchParams.delete('finance_search');
                        window.location.href = url.toString();
                    }
                }
            });

            window.addEventListener('resize', () => {
                chart.data.datasets[0].backgroundColor = barColors();
                chart.update('none');
            });
        })();
    </script>
</x-admin-layout>
