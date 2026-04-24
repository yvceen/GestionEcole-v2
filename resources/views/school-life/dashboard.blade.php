<x-school-life-layout title="Tableau de bord" subtitle="Vue rapide des priorites de la vie scolaire aujourd'hui.">
    <section class="grid gap-3 md:grid-cols-3">
        <x-ui.button :href="route('attendance.scan.page')" variant="primary">Scanner les cartes</x-ui.button>
        <x-ui.button :href="route('school-life.cards.index')" variant="secondary">Voir les cartes</x-ui.button>
        <x-ui.button :href="route('school-life.calendar.index')" variant="secondary">Ouvrir le calendrier</x-ui.button>
    </section>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="app-stat-card">
            <p class="app-stat-label">Eleves</p>
            <p class="app-stat-value">{{ $stats['students'] }}</p>
            <p class="app-stat-meta">Dossiers visibles dans l'ecole active.</p>
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
    </section>

    <section class="grid gap-6 xl:grid-cols-2">
        <x-ui.card title="Absences et retards recents" subtitle="Derniers signalements a consulter.">
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
                    <div class="student-empty">Aucun signalement recent.</div>
                @endforelse
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
                    <div class="student-empty">Aucune demande active.</div>
                @endforelse
            </div>
        </x-ui.card>
    </section>
</x-school-life-layout>
