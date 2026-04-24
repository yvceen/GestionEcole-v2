<x-parent-layout title="Activites scolaires" subtitle="Consultez les activites de vos enfants et confirmez rapidement leur participation.">
    <section class="grid gap-4 sm:grid-cols-3">
        <article class="student-kpi">
            <p class="student-kpi-label">Enfants concernes</p>
            <p class="student-kpi-value">{{ $children->count() }}</p>
            <p class="student-kpi-copy">Associes aux activites scolaires.</p>
        </article>
        <article class="student-kpi">
            <p class="student-kpi-label">Activites</p>
            <p class="student-kpi-value">{{ $activities->total() }}</p>
            <p class="student-kpi-copy">Dans l ecole active.</p>
        </article>
        <article class="student-kpi">
            <p class="student-kpi-label">Confirmations en attente</p>
            <p class="student-kpi-value">
                {{ $activities->getCollection()->flatMap->participants->where('confirmation_status', \App\Models\ActivityParticipant::CONFIRMATION_PENDING)->count() }}
            </p>
            <p class="student-kpi-copy">A valider depuis ce tableau.</p>
        </article>
    </section>

    <x-ui.card title="Activites de vos enfants" subtitle="Vous pouvez confirmer ou refuser la participation par enfant.">
        <div class="space-y-4">
            @forelse($activities as $activity)
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
                                Classe: {{ $activity->classroom?->name ?? 'Toutes' }}
                            </p>
                        </div>
                        @if($activity->teacher?->name)
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                                {{ $activity->teacher->name }}
                            </span>
                        @endif
                    </div>

                    @if(filled($activity->description))
                        <p class="mt-3 text-sm text-slate-600">{{ $activity->description }}</p>
                    @endif

                    <div class="mt-4 space-y-3">
                        @foreach($activity->participants as $participant)
                            @php
                                $status = (string) $participant->confirmation_status;
                                $variant = match ($status) {
                                    \App\Models\ActivityParticipant::CONFIRMATION_CONFIRMED => 'success',
                                    \App\Models\ActivityParticipant::CONFIRMATION_DECLINED => 'danger',
                                    default => 'warning',
                                };
                                $label = match ($status) {
                                    \App\Models\ActivityParticipant::CONFIRMATION_CONFIRMED => 'Confirmee',
                                    \App\Models\ActivityParticipant::CONFIRMATION_DECLINED => 'Refusee',
                                    default => 'En attente',
                                };
                            @endphp
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                    <div>
                                        <p class="font-semibold text-slate-900">{{ $participant->student?->full_name ?? '-' }}</p>
                                        <p class="text-xs text-slate-500">{{ $participant->student?->classroom?->name ?? '-' }}</p>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <x-ui.badge :variant="$variant">{{ $label }}</x-ui.badge>
                                        <form method="POST" action="{{ route('parent.activities.confirm', $activity) }}">
                                            @csrf
                                            <input type="hidden" name="student_id" value="{{ $participant->student_id }}">
                                            <input type="hidden" name="confirmation_status" value="{{ \App\Models\ActivityParticipant::CONFIRMATION_CONFIRMED }}">
                                            <x-ui.button type="submit" variant="primary" size="sm">Confirmer</x-ui.button>
                                        </form>
                                        <form method="POST" action="{{ route('parent.activities.confirm', $activity) }}">
                                            @csrf
                                            <input type="hidden" name="student_id" value="{{ $participant->student_id }}">
                                            <input type="hidden" name="confirmation_status" value="{{ \App\Models\ActivityParticipant::CONFIRMATION_DECLINED }}">
                                            <x-ui.button type="submit" variant="secondary" size="sm">Refuser</x-ui.button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if($activity->reports->isNotEmpty())
                        <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Compte rendu vie scolaire</p>
                            <div class="mt-2 space-y-2">
                                @foreach($activity->reports->take(2) as $report)
                                    <article class="text-sm text-slate-700">
                                        <p class="text-xs text-slate-500">{{ $report->created_at?->format('d/m/Y H:i') }} - {{ $report->author?->name ?? '-' }}</p>
                                        <p class="mt-1">{{ $report->report_text }}</p>
                                    </article>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </article>
            @empty
                <div class="student-empty">Aucune activite associee a vos enfants pour le moment.</div>
            @endforelse
        </div>

        <div class="mt-4">{{ $activities->links() }}</div>
    </x-ui.card>
</x-parent-layout>
