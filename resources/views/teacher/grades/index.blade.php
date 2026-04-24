<x-teacher-layout title="Saisie des notes">
    <div class="rounded-[28px] border border-black/5 bg-white/70 backdrop-blur-2xl p-6 shadow-[0_22px_60px_-40px_rgba(0,0,0,.50)]">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-900">Saisie des notes</h1>
            <p class="text-sm text-slate-600 mt-1">Choisissez une évaluation, puis saisissez les notes des élèves.</p>
        </div>

        {{-- Filters --}}
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label class="text-xs font-semibold text-slate-600">Classe</label>
                <select name="classroom_id" class="mt-1 w-full rounded-2xl border border-black/10 bg-white px-3 py-2 text-sm">
                    <option value="">Toutes les classes</option>
                    @foreach($classrooms as $c)
                        <option value="{{ $c->id }}" @selected((string)$classroomId === (string)$c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-xs font-semibold text-slate-600">Matière</label>
                <select name="subject_id" class="mt-1 w-full rounded-2xl border border-black/10 bg-white px-3 py-2 text-sm">
                    <option value="">Toutes les matières</option>
                    @foreach($subjects as $s)
                        <option value="{{ $s->id }}" @selected((string)$subjectId === (string)$s->id)>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-xs font-semibold text-slate-600">Évaluation</label>
                <select name="assessment_id" class="mt-1 w-full rounded-2xl border border-black/10 bg-white px-3 py-2 text-sm">
                    <option value="">Choisir...</option>
                    @foreach($assessments as $a)
                        <option value="{{ $a->id }}" @selected((string)$assessmentId === (string)$a->id)>
                            {{ $a->title ?? ('Évaluation #'.$a->id) }} — {{ optional($a->date)->format('d/m/Y') ?? '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-2">
                <button class="w-full rounded-2xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-black">
                    Filtrer
                </button>
                <a href="{{ route('teacher.grades.index') }}"
                   class="rounded-2xl border border-black/10 bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-white/80">
                    Reset
                </a>
            </div>
        </form>

        <div class="mt-4 text-xs text-slate-500">
            Astuce : sélectionnez une évaluation pour activer la saisie.
        </div>

        {{-- Students list --}}
        <div class="mt-6 rounded-3xl border border-black/5 bg-white p-5">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-bold text-slate-900">Liste des élèves</h2>

                @if($selectedAssessment)
                    <div class="text-xs text-slate-500">
                        Évaluation: <span class="font-semibold text-slate-800">{{ $selectedAssessment->title ?? ('#'.$selectedAssessment->id) }}</span>
                        @if($selectedAssessment->date)
                            • <span>{{ $selectedAssessment->date->format('d/m/Y') }}</span>
                        @endif
                    </div>
                @endif
            </div>

            @if(!$selectedAssessment)
                <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-amber-900">
                    Veuillez choisir une <b>évaluation</b> pour saisir les notes.
                </div>
            @elseif($students->isEmpty())
                <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-amber-900">
                    Aucun élève trouvé pour cette classe (ou classroom_id غير موجود فـ l’évaluation).
                </div>
            @else
                <form method="POST" action="{{ route('teacher.grades.store') }}" class="mt-4">
                    @csrf
                    <input type="hidden" name="assessment_id" value="{{ $selectedAssessment->id }}"/>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-slate-600">
                                    <th class="py-2">Élève</th>
                                    <th class="py-2 w-40">Note / {{ $selectedAssessment->max_score ?? 20 }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-black/5">
                                @foreach($students as $st)
                                    <tr>
                                        <td class="py-3 font-semibold text-slate-900">{{ $st->full_name }}</td>
                                        <td class="py-3">
                                            <input
                                                type="number"
                                                step="0.25"
                                                min="0"
                                                max="{{ $selectedAssessment->max_score ?? 20 }}"
                                                name="scores[{{ $st->id }}]"
                                                class="w-full rounded-2xl border border-black/10 bg-white px-3 py-2"
                                                placeholder="Ex: 15.5"
                                            />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 flex justify-end">
                        <button class="rounded-2xl bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-emerald-700">
                            Enregistrer les notes
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</x-teacher-layout>
