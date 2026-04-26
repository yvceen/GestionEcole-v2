<x-school-life-layout title="Tableau de bord" subtitle="Vue rapide des priorites de la vie scolaire aujourd'hui.">
    <x-ui.page-header
        title="Vue generale"
        subtitle="Retrouvez les priorites du jour, les incidents a suivre et les actions operationnelles les plus utiles."
    >
        <x-slot name="actions">
            <x-ui.button :href="route('attendance.scan.page')" variant="primary">Scanner les cartes</x-ui.button>
            <x-ui.button :href="route('school-life.pickup-requests.index')" variant="secondary">Voir les recuperations</x-ui.button>
            <x-ui.button :href="route('school-life.calendar.index')" variant="ghost">Ouvrir le calendrier</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <section class="space-y-4">
        <div class="flex items-end justify-between gap-4">
            <div>
                <p class="app-overline">Vue generale</p>
                <h2 class="app-section-title mt-2">Chiffres cles du jour</h2>
            </div>
            <p class="app-muted max-w-2xl">Une lecture rapide pour prioriser les presences, les retards et les recuperations.</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="app-stat-card">
                <p class="app-stat-label">Eleves</p>
                <p class="app-stat-value">{{ $stats['students'] }}</p>
                <p class="app-stat-meta">Dossiers visibles dans l ecole active.</p>
            </article>
            <article class="app-stat-card">
                <p class="app-stat-label">Absences du jour</p>
                <p class="app-stat-value text-rose-700">{{ $stats['today_absent'] }}</p>
                <p class="app-stat-meta">A suivre avec les familles.</p>
            </article>
            <article class="app-stat-card">
                <p class="app-stat-label">Retards du jour</p>
                <p class="app-stat-value text-amber-700">{{ $stats['today_late'] }}</p>
                <p class="app-stat-meta">Retards saisis par les enseignants.</p>
            </article>
            <article class="app-stat-card">
                <p class="app-stat-label">Recuperations</p>
                <p class="app-stat-value text-sky-700">{{ $stats['pickup_pending'] }}</p>
                <p class="app-stat-meta">Demandes en attente de decision.</p>
            </article>
        </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-[minmax(0,1.2fr)_minmax(320px,0.8fr)]">
        <x-ui.card title="Activite du jour" subtitle="Derniers signalements a traiter rapidement.">
            <div class="space-y-3">
                @forelse($recentAttendance as $attendance)
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-semibold text-slate-950">{{ $attendance->student?->full_name ?? '-' }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $attendance->student?->classroom?->name ?? '-' }} | {{ $attendance->date?->format('d/m/Y') }}</p>
                            </div>
                            <x-ui.badge :variant="$attendance->status === 'late' ? 'warning' : 'danger'">
                                {{ $attendance->status === 'late' ? 'En retard' : 'Absent' }}
                            </x-ui.badge>
                        </div>
                    </div>
                @empty
                    <div class="app-empty-state">
                        <div class="app-empty-state-icon">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 12l3 3 7-7" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 12a8 8 0 1 0 16 0 8 8 0 1 0-16 0" />
                            </svg>
                        </div>
                        <p class="app-empty-state-title">Aucun signalement recent</p>
                        <p class="app-empty-state-copy">Les absences et retards recents apparaitront ici des qu ils seront saisis.</p>
                        <div class="app-empty-state-actions">
                            <x-ui.button :href="route('school-life.attendance.index')" variant="secondary">Ouvrir les presences</x-ui.button>
                        </div>
                    </div>
                @endforelse
            </div>
        </x-ui.card>

        <div class="space-y-6">
            <x-ui.card title="Actions rapides" subtitle="Accedez aux ecrans les plus utiles sans perdre de temps.">
                <div class="grid gap-3">
                    <x-ui.button :href="route('attendance.scan.page')" variant="primary">Scanner les cartes</x-ui.button>
                    <x-ui.button :href="route('school-life.cards.index')" variant="secondary">Voir les cartes</x-ui.button>
                    <x-ui.button :href="route('school-life.students.index')" variant="secondary">Suivre les eleves</x-ui.button>
                    <x-ui.button :href="route('transport.ops.index')" variant="ghost">Ouvrir le transport</x-ui.button>
                </div>
            </x-ui.card>

            <x-ui.card title="Demandes de recuperation" subtitle="Demandes parent en attente ou approuvees.">
                <div class="space-y-3">
                    @forelse($pickupRequests as $request)
                        <a href="{{ route('school-life.pickup-requests.index') }}" class="block rounded-2xl border border-slate-200 bg-white px-4 py-4 transition hover:bg-slate-50">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="font-semibold text-slate-950">{{ $request->student?->full_name ?? '-' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $request->parentUser?->name ?? '-' }} | {{ $request->parentUser?->phone ?? '-' }}</p>
                                </div>
                                <x-ui.badge :variant="$request->status === 'approved' ? 'success' : 'warning'">
                                    {{ $request->status }}
                                </x-ui.badge>
                            </div>
                            <p class="mt-2 text-sm text-slate-600">{{ $request->requested_pickup_at?->format('d/m/Y H:i') }}</p>
                        </a>
                    @empty
                        <div class="app-empty-state">
                            <div class="app-empty-state-icon">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h8M12 8v8" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 5h14v14H5z" />
                                </svg>
                            </div>
                            <p class="app-empty-state-title">Aucune demande active</p>
                            <p class="app-empty-state-copy">Les demandes de recuperation apparaitront ici des qu elles seront envoyees par les familles.</p>
                            <div class="app-empty-state-actions">
                                <x-ui.button :href="route('school-life.pickup-requests.index')" variant="secondary">Voir l historique</x-ui.button>
                            </div>
                        </div>
                    @endforelse
                </div>
            </x-ui.card>
        </div>
    </section>
</x-school-life-layout>
