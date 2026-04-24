<x-director-layout title="Élèves en difficulté">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Élèves en difficulté</h1>
            <p class="mt-1 text-sm text-slate-500">
                Top 20 des élèves avec une moyenne &lt; {{ $threshold ?? 10 }}/20 (selon la période choisie).
            </p>
        </div>

        <div class="rounded-2xl border border-black/10 bg-white/80 px-4 py-3 text-xs text-slate-600 shadow-sm">
            Période analysée depuis :
            <span class="font-semibold text-slate-900">{{ optional($from)->format('Y-m-d') }}</span>
        </div>
    </div>

    {{-- Filters --}}
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
                <label class="block text-xs font-semibold text-slate-600 mb-1">Période</label>
                <select name="period" class="w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm">
                    <option value="month" @selected(($period ?? 'month')==='month')>Ce mois</option>
                    <option value="trimester" @selected(($period ?? '')==='trimester')>Trimestre</option>
                    <option value="year" @selected(($period ?? '')==='year')>Année</option>
                </select>
            </div>

            <div class="md:col-span-3 flex items-end gap-2">
                <button class="w-full rounded-2xl bg-black px-6 py-2.5 text-sm font-semibold text-white hover:bg-slate-900">
                    Afficher
                </button>

                <a href="{{ url()->current() }}" class="rounded-2xl border border-black/10 bg-white px-6 py-2.5 text-sm font-semibold hover:bg-slate-50">
                    Réinitialiser
                </a>
            </div>
        </div>

        <div class="mt-4 text-xs text-slate-500">
            Astuce : filtre par classe باش يشوف المدير فين خاص الدعم أكثر.
        </div>
    </form>

    {{-- List --}}
    <div class="mt-6 overflow-hidden rounded-[28px] border border-black/10 bg-white/80 shadow-sm">
        <div class="flex items-center justify-between border-b border-black/10 bg-white/60 px-5 py-4">
            <div class="text-sm font-semibold text-slate-900">Liste (Top 20)</div>
            <div class="text-xs text-slate-500">
                Élèves : <span class="font-semibold text-slate-900">{{ count($items ?? []) }}</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50/80">
                    <tr class="text-left border-b border-black/5">
                        <th class="p-4 text-xs font-semibold text-slate-500">Élève</th>
                        <th class="p-4 text-xs font-semibold text-slate-500">Classe</th>
                        <th class="p-4 text-xs font-semibold text-slate-500">Moyenne</th>
                        <th class="p-4 text-xs font-semibold text-slate-500">Notes</th>
                        <th class="p-4 text-xs font-semibold text-slate-500">Matières faibles</th>
                        <th class="p-4 text-xs font-semibold text-slate-500">Action proposée</th>
                        <th class="p-4 text-xs font-semibold text-slate-500"></th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-black/5">
                    @forelse(($items ?? []) as $it)
                        @php
                            $critical = !empty($it['is_critical']);
                        @endphp

                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="p-4">
                                <div class="font-semibold text-slate-900">{{ $it['student_name'] ?? '—' }}</div>
                                <div class="text-xs text-slate-500">ID: {{ $it['student_id'] ?? '—' }}</div>
                            </td>

                            <td class="p-4 text-slate-700">{{ $it['classroom'] ?? '—' }}</td>

                            <td class="p-4">
                                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold border
                                    {{ $critical ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-amber-50 text-amber-700 border-amber-200' }}">
                                    {{ $it['avg'] ?? '—' }}
                                </span>
                            </td>

                            <td class="p-4 text-slate-700">
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                                    {{ $it['notes_count'] ?? 0 }}
                                </span>
                            </td>

                            <td class="p-4 text-slate-700">
                                @if(!empty($it['weak_subjects']))
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($it['weak_subjects'] as $ws)
                                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                                {{ $ws }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    —
                                @endif
                            </td>

                            <td class="p-4 text-slate-700">
                                <span class="inline-flex items-center rounded-2xl border border-black/10 bg-white px-3 py-2 text-xs font-semibold">
                                    {{ $it['action'] ?? 'Soutien ciblé + suivi hebdomadaire' }}
                                </span>
                            </td>

                            <td class="p-4 text-right">
                                @if(\Illuminate\Support\Facades\Route::has('director.students.fiche'))
                                    <a href="{{ route('director.students.fiche', $it['student_id']) }}"
                                       class="inline-flex items-center rounded-2xl border border-black/10 bg-white px-4 py-2 text-xs font-semibold hover:bg-slate-50">
                                        Voir fiche →
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-10 text-center text-slate-500">
                                Aucun élève en difficulté pour les filtres choisis.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-black/10 bg-white/60 px-5 py-4 text-xs text-slate-500">
            Définition : “en difficulté” = moyenne &lt; {{ $threshold ?? 10 }}/20 sur la période (basé sur les notes enregistrées).
        </div>
    </div>
</x-director-layout>