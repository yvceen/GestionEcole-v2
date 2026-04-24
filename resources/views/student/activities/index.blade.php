<x-student-layout title="Mes activites" subtitle="Retrouvez les activites de votre ecole et le suivi de votre participation.">
    <section class="grid gap-4 sm:grid-cols-3">
        <article class="student-kpi">
            <p class="student-kpi-label">Activites</p>
            <p class="student-kpi-value">{{ $activities->total() }}</p>
            <p class="student-kpi-copy">Associees a votre profil eleve.</p>
        </article>
        <article class="student-kpi">
            <p class="student-kpi-label">Classe</p>
            <p class="student-kpi-value text-xl">{{ $student->classroom?->name ?? '-' }}</p>
            <p class="student-kpi-copy">Classe actuellement rattachee.</p>
        </article>
        <article class="student-kpi">
            <p class="student-kpi-label">Confirmations</p>
            <p class="student-kpi-value">
                {{ $activities->getCollection()->flatMap->participants->where('confirmation_status', \App\Models\ActivityParticipant::CONFIRMATION_CONFIRMED)->count() }}
            </p>
            <p class="student-kpi-copy">Participations validees.</p>
        </article>
    </section>

    <x-ui.card title="Liste des activites" subtitle="Visualisez les details et le statut de votre participation.">
        <div class="space-y-4">
            @forelse($activities as $activity)
                @php($participant = $activity->participants->first())
                @php
                    $confirmation = (string) ($participant?->confirmation_status ?? \App\Models\ActivityParticipant::CONFIRMATION_PENDING);
                    $variantInput = $confirmationVariant ?? match ($confirmation) {
                        \App\Models\ActivityParticipant::CONFIRMATION_CONFIRMED => 'success',
                        \App\Models\ActivityParticipant::CONFIRMATION_DECLINED => 'danger',
                        default => 'warning',
                    };
                    $confirmationVariant = match ($variantInput) {
                        'success' => 'success',
                        'warning' => 'warning',
                        'danger' => 'danger',
                        default => 'danger',
                    };
                    $attendance = (string) ($participant?->attendance_status ?? '');
                    $attendanceVariant = $attendance === \App\Models\ActivityParticipant::ATTENDANCE_PRESENT ? 'success' : ($attendance === \App\Models\ActivityParticipant::ATTENDANCE_ABSENT ? 'danger' : 'info');
                @endphp
                <article class="rounded-3xl border border-slate-200 bg-white p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-base font-semibold text-slate-950">{{ $activity->title }}</h3>
                                <span class="inline-flex h-2.5 w-2.5 rounded-full" style="background-color: {{ $activity->color ?: \App\Models\Activity::defaultColorForType((string) $activity->type) }}"></span>
                            </div>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ \App\Models\Activity::labelForType((string) $activity->type) }}
                                <span class="mx-1 text-slate-300">|</span>
                                {{ $activity->start_date?->format('d/m/Y H:i') }} - {{ $activity->end_date?->format('d/m/Y H:i') }}
                                <span class="mx-1 text-slate-300">|</span>
                                Enseignant: {{ $activity->teacher?->name ?? '-' }}
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <x-ui.badge :variant="$confirmationVariant">
                                {{ $confirmation === 'confirmed' ? 'Participation confirmee' : ($confirmation === 'declined' ? 'Participation refusee' : 'Participation en attente') }}
                            </x-ui.badge>
                            <x-ui.badge :variant="$attendanceVariant">
                                {{ $attendance === 'present' ? 'Presence marquee' : ($attendance === 'absent' ? 'Absence marquee' : 'Presence non marquee') }}
                            </x-ui.badge>
                        </div>
                    </div>

                    @if(filled($activity->description))
                        <p class="mt-3 text-sm text-slate-600">{{ $activity->description }}</p>
                    @endif

                    @if($activity->reports->isNotEmpty())
                        <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Dernier compte rendu</p>
                            @php($lastReport = $activity->reports->first())
                            <p class="mt-2 text-xs text-slate-500">{{ $lastReport?->created_at?->format('d/m/Y H:i') }} - {{ $lastReport?->author?->name ?? '-' }}</p>
                            <p class="mt-1 text-sm text-slate-700">{{ $lastReport?->report_text }}</p>
                        </div>
                    @endif
                </article>
            @empty
                <div class="student-empty">Aucune activite disponible pour votre profil.</div>
            @endforelse
        </div>

        <div class="mt-4">{{ $activities->links() }}</div>
    </x-ui.card>
</x-student-layout>
