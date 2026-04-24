<x-admin-layout title="Tableau de bord">
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

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-8">
        <div class="app-stat-card">
            <p class="app-stat-label">Eleves</p>
            <p class="app-stat-value">{{ $studentsCount }}</p>
            <p class="app-stat-meta">Effectif total enregistre.</p>
        </div>

        <div class="app-stat-card">
            <p class="app-stat-label">Classes</p>
            <p class="app-stat-value">{{ $classroomsCount }}</p>
            <p class="app-stat-meta">Structure actuellement active.</p>
        </div>

        <div class="app-stat-card">
            <p class="app-stat-label">Utilisateurs</p>
            <p class="app-stat-value">{{ $usersCount }}</p>
            <div class="mt-3 flex flex-wrap gap-2">
                <x-ui.badge variant="info">Parents : {{ $parentsCount }}</x-ui.badge>
                <x-ui.badge variant="info">Enseignants : {{ $teachersCount }}</x-ui.badge>
            </div>
        </div>

        <div class="app-stat-card">
            <p class="app-stat-label">Enseignants</p>
            <p class="app-stat-value">{{ $teachersCount }}</p>
            <p class="app-stat-meta">Corps enseignant de l'etablissement.</p>
        </div>

        <div class="app-stat-card">
            <div class="flex items-center justify-between gap-2">
                <p class="app-stat-label">Revenus du mois</p>
                <x-ui.badge variant="warning">MAD</x-ui.badge>
            </div>
            <p class="app-stat-value">{{ number_format($revenueThisMonth, 2) }}</p>
            <p class="app-stat-meta">Cliquez sur un mois pour afficher le detail des paiements.</p>
        </div>

        <div class="app-stat-card">
            <p class="app-stat-label">Presences du jour</p>
            <p class="app-stat-value text-emerald-700">{{ (int) ($attendanceSummary['today_present'] ?? 0) }}</p>
            <p class="app-stat-meta">Eleves pointes presents aujourd'hui.</p>
        </div>

        <div class="app-stat-card">
            <p class="app-stat-label">Absences du jour</p>
            <p class="app-stat-value text-rose-700">{{ (int) ($attendanceSummary['today_absent'] ?? 0) }}</p>
            <p class="app-stat-meta">Absences remontees aujourd'hui dans l'ecole active.</p>
        </div>

        <div class="app-stat-card">
            <p class="app-stat-label">Retards du jour</p>
            <p class="app-stat-value text-amber-700">{{ (int) ($attendanceSummary['today_late'] ?? 0) }}</p>
            <p class="app-stat-meta">Retards saisis par les enseignants sur la journee.</p>
        </div>
    </section>

    <x-ui.card title="Vue hebdomadaire des presences" subtitle="Lecture rapide des absences et retards deja remontes cette semaine.">
        <div class="grid gap-3 md:grid-cols-7">
            @forelse(($attendanceSummary['weekly_overview'] ?? collect()) as $day)
                <div class="rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ $day['label'] }}</p>
                    <div class="mt-3 space-y-2">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-500">Absences</span>
                            <span class="font-semibold text-rose-700">{{ $day['absent'] }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-500">Retards</span>
                            <span class="font-semibold text-amber-700">{{ $day['late'] }}</span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50/90 px-6 py-8 text-sm text-slate-500 md:col-span-7">
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

    <section class="grid gap-6 xl:grid-cols-[minmax(0,2fr)_360px]">
        <x-ui.card title="Revenus sur 12 mois" subtitle="Selectionnez un point pour consulter les paiements du mois correspondant.">
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

        <x-ui.card title="Detail du mois" :subtitle="!empty($selected) ? 'Mois : '.$selected : 'Selectionnez un mois dans le graphique.'">
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
