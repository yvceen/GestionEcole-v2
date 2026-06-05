<x-parent-layout title="Mes enfants" subtitle="Accédez rapidement au dossier scolaire, aux présences, au planning et au suivi financier de chaque enfant.">
    <section class="space-y-4">
        @if($children->isEmpty())
            <div class="student-empty">
                Aucun Élève lié a ce compte parent.
            </div>
        @else
            <div class="grid gap-4 lg:grid-cols-2">
                @foreach($children as $child)
                    @php
                        $average = $gradeAverages[$child->id] ?? null;
                        $attendance = $attendanceSummary[$child->id] ?? ['absent' => 0, 'late' => 0];
                        $payments = $paymentsSummary[$child->id] ?? ['count' => 0, 'total' => 0];
                    @endphp
                    <article class="student-panel">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <p class="student-eyebrow">Enfant</p>
                                <h2 class="mt-2 text-xl font-semibold text-slate-950">{{ $child->full_name }}</h2>
                                <p class="mt-2 text-sm text-slate-600">
                                    {{ $child->classroom?->name ?? '-' }}
                                    @if($child->classroom?->level?->name)
                                        <span class="mx-2 text-slate-300">|</span>{{ $child->classroom->level->name }}
                                    @endif
                                </p>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-left sm:text-right">
                                <p class="text-[0.72rem] font-semibold uppercase tracking-[0.16em] text-slate-500">Moyenne</p>
                                <p class="mt-2 text-2xl font-semibold text-slate-950">{{ $average !== null ? number_format((float) $average, 2) . '%' : '-' }}</p>
                            </div>
                        </div>

                        <div class="mt-5 grid gap-3 sm:grid-cols-3">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Absences</p>
                                <p class="mt-2 text-xl font-semibold text-slate-950">{{ $attendance['absent'] }}</p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Retards</p>
                                <p class="mt-2 text-xl font-semibold text-slate-950">{{ $attendance['late'] }}</p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Paiements</p>
                                <p class="mt-2 text-xl font-semibold text-slate-950">{{ $payments['count'] }}</p>
                            </div>
                        </div>

                        <div class="mt-5 grid grid-cols-2 gap-2 sm:grid-cols-2 xl:grid-cols-3">
                            <a href="{{ route('parent.children.courses', $child) }}" data-loading-label="Ouverture des cours..." class="app-button-soft rounded-2xl px-3 py-3 text-center text-sm font-semibold text-slate-900">Cours</a>
                            <a href="{{ route('parent.children.homeworks', $child) }}" data-loading-label="Ouverture des devoirs..." class="app-button-soft rounded-2xl px-3 py-3 text-center text-sm font-semibold text-slate-900">Devoirs</a>
                            <a href="{{ route('parent.children.grades', $child) }}" data-loading-label="Ouverture des notes..." class="app-button-soft rounded-2xl px-3 py-3 text-center text-sm font-semibold text-slate-900">Notes</a>
                            <a href="{{ route('parent.children.attendance', $child) }}" data-loading-label="Ouverture des présences..." class="app-button-soft rounded-2xl px-3 py-3 text-center text-sm font-semibold text-slate-900">Présence</a>
                            <a href="{{ route('parent.children.timetable', $child) }}" data-loading-label="Ouverture de l'emploi du temps..." class="app-button-soft rounded-2xl px-3 py-3 text-center text-sm font-semibold text-slate-900">Emploi du temps</a>
                            <a href="{{ route('parent.children.finance', $child) }}" data-loading-label="Ouverture de la finance..." class="app-button-soft rounded-2xl px-3 py-3 text-center text-sm font-semibold text-slate-900">Finance</a>
                        </div>

                        <p class="mt-4 text-sm text-slate-500">
                            Total payé : <span class="font-semibold text-slate-950">{{ number_format((float) $payments['total'], 2) }} MAD</span>
                        </p>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</x-parent-layout>
