<x-director-layout title="Résultats & Analyses">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Résultats & Analyses</h1>
            <p class="mt-1 text-sm text-slate-500">
                Suivi des notes par classe, matière et enseignant + détection des élèves en difficulté.
            </p>
        </div>
    </div>

    {{-- KPI --}}
    <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="rounded-[28px] border border-black/10 bg-white/80 p-5 shadow-sm">
            <div class="text-xs font-semibold text-slate-500">Total des notes (filtres inclus)</div>
            <div class="mt-2 text-3xl font-semibold text-slate-900">{{ $totalNotes ?? 0 }}</div>
            <div class="mt-1 text-xs text-slate-500">Basé sur les notes saisies par les enseignants.</div>
        </div>

        <div class="rounded-[28px] border border-black/10 bg-white/80 p-5 shadow-sm">
            <div class="text-xs font-semibold text-slate-500">Moyenne globale</div>
            <div class="mt-2 text-3xl font-semibold text-slate-900">
                {{ $globalAvg !== null ? number_format((float)$globalAvg, 2) : '—' }}
                <span class="text-sm font-semibold text-slate-500">/20</span>
            </div>
            <div class="mt-1 text-xs text-slate-500">Moyenne calculée sur les notes filtrées.</div>
        </div>

        <div class="rounded-[28px] border border-black/10 bg-white/80 p-5 shadow-sm">
            <div class="text-xs font-semibold text-slate-500">Élèves en difficulté</div>
            <div class="mt-2 text-3xl font-semibold text-slate-900">{{ $studentsDifficultyCount ?? 0 }}</div>
            <div class="mt-1 text-xs text-slate-500">
                Seuil actuel : moyenne &lt; {{ $difficultyThreshold ?? 10 }} /20 (modifiable).
            </div>
        </div>
    </div>

    {{-- FILTERS --}}
    <form method="GET" class="mt-6 rounded-[28px] border border-black/10 bg-white/80 p-5 shadow-sm">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-5">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Classe</label>
                <select name="classroom_id" class="w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm">
                    <option value="">Toutes</option>
                    @foreach(($classrooms ?? []) as $c)
                        <option value="{{ $c->id }}" @selected((string)$classroomId === (string)$c->id)>
                            {{ $c->name ?? ('Classe #' . $c->id) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Matière</label>
                <select name="subject_id" class="w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm">
                    <option value="">Toutes</option>
                    @foreach(($subjects ?? []) as $s)
                        <option value="{{ $s->id }}" @selected((string)$subjectId === (string)$s->id)>
                            {{ $s->name ?? ('Matière #' . $s->id) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Enseignant</label>
                <select name="teacher_id" class="w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm">
                    <option value="">Tous</option>
                    @foreach(($teachers ?? []) as $t)
                        <option value="{{ $t->id }}" @selected((string)$teacherId === (string)$t->id)>
                            {{ $t->name ?? ('Prof #' . $t->id) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Du</label>
                <input type="date" name="from" value="{{ $from }}"
                       class="w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm" />
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Au</label>
                <input type="date" name="to" value="{{ $to }}"
                       class="w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm" />
            </div>
        </div>

        <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div class="text-xs text-slate-500">
                Conseil : filtre par mois (Du/Au) باش تشوف الإحصائيات بوضوح.
            </div>

            <div class="flex gap-2">
                <button class="rounded-2xl bg-black px-6 py-2.5 text-sm font-semibold text-white hover:bg-slate-900">
                    Filtrer
                </button>
                <a href="{{ url()->current() }}" class="rounded-2xl border border-black/10 bg-white px-6 py-2.5 text-sm font-semibold hover:bg-slate-50">
                    Réinitialiser
                </a>
            </div>
        </div>
    </form>

    {{-- SUMMARY TABLE --}}
    <div class="mt-6 overflow-hidden rounded-[28px] border border-black/10 bg-white/80 shadow-sm">
        <div class="flex items-center justify-between border-b border-black/10 bg-white/60 px-5 py-4">
            <div class="text-sm font-semibold text-slate-900">Synthèse (moyennes)</div>
            <div class="text-xs text-slate-500">
                Lignes : <span class="font-semibold text-slate-900">{{ count($rows ?? []) }}</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50/80">
                    <tr class="text-left border-b border-black/5">
                        <th class="p-4 text-xs font-semibold text-slate-500">Classe</th>
                        <th class="p-4 text-xs font-semibold text-slate-500">Matière</th>
                        <th class="p-4 text-xs font-semibold text-slate-500">Enseignant</th>
                        <th class="p-4 text-xs font-semibold text-slate-500">Moyenne</th>
                        <th class="p-4 text-xs font-semibold text-slate-500">Min</th>
                        <th class="p-4 text-xs font-semibold text-slate-500">Max</th>
                        <th class="p-4 text-xs font-semibold text-slate-500">Notes</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-black/5">
                    @forelse(($rows ?? []) as $r)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="p-4 font-semibold text-slate-900">{{ $r['classroom'] ?? '—' }}</td>
                            <td class="p-4 text-slate-700">{{ $r['subject'] ?? '—' }}</td>
                            <td class="p-4 text-slate-700">{{ $r['teacher'] ?? '—' }}</td>
                            <td class="p-4">
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                                    {{ $r['avg'] ?? '—' }} /20
                                </span>
                            </td>
                            <td class="p-4 text-slate-700">{{ $r['min'] ?? '—' }}</td>
                            <td class="p-4 text-slate-700">{{ $r['max'] ?? '—' }}</td>
                            <td class="p-4 text-slate-700">{{ $r['count'] ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-10 text-center text-slate-500">
                                Aucune donnée pour les filtres choisis.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- DETAILED GRADES LIST --}}
    <div class="mt-6 overflow-hidden rounded-[28px] border border-black/10 bg-white/80 shadow-sm">
        <div class="flex items-center justify-between border-b border-black/10 bg-white/60 px-5 py-4">
            <div class="text-sm font-semibold text-slate-900">Dernières notes</div>
            <div class="text-xs text-slate-500">
                Total : <span class="font-semibold text-slate-900">{{ $grades?->total() ?? 0 }}</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50/80">
                    <tr class="text-left border-b border-black/5">
                        <th class="p-4 text-xs font-semibold text-slate-500">Élève</th>
                        <th class="p-4 text-xs font-semibold text-slate-500">Classe</th>
                        <th class="p-4 text-xs font-semibold text-slate-500">Matière</th>
                        <th class="p-4 text-xs font-semibold text-slate-500">Enseignant</th>
                        <th class="p-4 text-xs font-semibold text-slate-500">Note</th>
                        <th class="p-4 text-xs font-semibold text-slate-500">Évaluation</th>
                        <th class="p-4 text-xs font-semibold text-slate-500">Date</th>
                        <th class="p-4 text-xs font-semibold text-slate-500"></th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-black/5">
                    @forelse(($grades ?? []) as $g)
                        @php
                            $score = $g->score !== null ? (float)$g->score : null;
                            $max   = $g->max_score ?? 20;
                            $isLow = $score !== null && $score < ($difficultyThreshold ?? 10);
                        @endphp

                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="p-4 font-semibold text-slate-900">
                                {{ $g->student?->full_name ?? ('Élève #' . $g->student_id) }}
                            </td>
                            <td class="p-4 text-slate-700">{{ $g->classroom?->name ?? ('Classe #' . $g->classroom_id) }}</td>
                            <td class="p-4 text-slate-700">{{ $g->subject?->name ?? ('Matière #' . $g->subject_id) }}</td>
                            <td class="p-4 text-slate-700">{{ $g->teacher?->name ?? ('Prof #' . $g->teacher_id) }}</td>

                            <td class="p-4">
                                @if($score !== null)
                                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold
                                        {{ $isLow ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700' }}">
                                        {{ number_format($score, 2) }} / {{ (int)$max }}
                                    </span>
                                @else
                                    —
                                @endif
                            </td>

                            <td class="p-4 text-slate-700">
                                {{ $g->assessment?->title ?? ('Éval #' . ($g->assessment_id ?? '—')) }}
                            </td>

                            <td class="p-4 text-slate-500">
                                {{ optional($g->created_at)->format('Y-m-d') }}
                            </td>

                            <td class="p-4 text-right">
                                @if(\Illuminate\Support\Facades\Route::has('director.students.fiche'))
                                    <a href="{{ route('director.students.fiche', $g->student_id) }}"
                                       class="inline-flex items-center rounded-2xl border border-black/10 bg-white px-4 py-2 text-xs font-semibold hover:bg-slate-50">
                                        Voir fiche →
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-10 text-center text-slate-500">
                                Aucune note enregistrée pour le moment.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($grades && $grades->hasPages())
            <div class="border-t border-black/10 bg-white/60 px-5 py-4">
                {{ $grades->links() }}
            </div>
        @endif
    </div>
</x-director-layout>