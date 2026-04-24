<x-parent-layout title="Notes" subtitle="Consultez les evaluations et moyennes de vos enfants avec un filtre simple par dossier eleve.">
    <section class="grid gap-4 lg:grid-cols-[minmax(0,1.2fr)_320px]">
        <div class="space-y-4">
            <section class="portal-filter-bar">
                <form method="GET" data-loading-label="Filtrage des notes..." class="portal-filter-grid md:grid-cols-[minmax(0,1fr)_auto]">
                    <select name="child_id" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                        <option value="">Tous mes enfants</option>
                        @foreach($children as $child)
                            <option value="{{ $child->id }}" @selected((string) $childId === (string) $child->id)>
                                {{ $child->full_name }} - {{ $child->classroom?->name ?? '-' }}
                            </option>
                        @endforeach
                    </select>
                    <div class="portal-filter-actions">
                        <button class="app-button-primary rounded-2xl px-4 py-3">Filtrer</button>
                        @if($childId)
                            <a href="{{ route('parent.grades.index') }}" class="app-button-secondary rounded-2xl px-4 py-3">
                                Reinitialiser
                            </a>
                        @endif
                    </div>
                </form>
            </section>

            <section class="student-panel">
                <div class="hidden overflow-x-auto md:block">
                    <table class="min-w-full text-sm">
                        <thead class="border-b border-slate-200 text-left text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">
                            <tr>
                                <th class="px-3 py-3">Eleve</th>
                                <th class="px-3 py-3">Matiere</th>
                                <th class="px-3 py-3">Evaluation</th>
                                <th class="px-3 py-3">Enseignant</th>
                                <th class="px-3 py-3">Score</th>
                                <th class="px-3 py-3">Taux</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($grades as $grade)
                                @php($percent = $grade->max_score ? round(((float) $grade->score / (int) $grade->max_score) * 100, 2) : 0)
                                <tr>
                                    <td class="px-3 py-4 font-semibold text-slate-950">{{ $grade->student?->full_name ?? '-' }}</td>
                                    <td class="px-3 py-4 text-slate-600">{{ $grade->subject?->name ?? '-' }}</td>
                                    <td class="px-3 py-4 text-slate-600">{{ $grade->assessment?->title ?? 'Evaluation' }}</td>
                                    <td class="px-3 py-4 text-slate-600">{{ $grade->teacher?->name ?? '-' }}</td>
                                    <td class="px-3 py-4 font-semibold text-slate-950">{{ number_format((float) $grade->score, 2) }} / {{ $grade->max_score ?? '-' }}</td>
                                    <td class="px-3 py-4"><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ number_format($percent, 2) }}%</span></td>
                                </tr>
                                @if(!empty($grade->comment))
                                    <tr>
                                        <td colspan="6" class="px-3 pb-4 text-xs text-slate-500">Commentaire : {{ $grade->comment }}</td>
                                    </tr>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="6" class="px-3 py-10 text-center text-sm text-slate-500">Aucune note disponible.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="portal-record-stack md:hidden">
                    @forelse($grades as $grade)
                        @php($percent = $grade->max_score ? round(((float) $grade->score / (int) $grade->max_score) * 100, 2) : 0)
                        <article class="portal-record-card">
                            <div class="portal-record-card-head">
                                <div class="min-w-0">
                                    <p class="portal-record-title">{{ $grade->student?->full_name ?? '-' }}</p>
                                    <p class="portal-record-subtitle">{{ $grade->subject?->name ?? '-' }} | {{ $grade->assessment?->title ?? 'Evaluation' }}</p>
                                </div>
                                <span class="rounded-full bg-slate-900 px-3 py-1 text-xs font-semibold text-white">
                                    {{ number_format($percent, 2) }}%
                                </span>
                            </div>
                            <div class="portal-record-meta">
                                <div class="portal-record-meta-item">
                                    <p class="portal-record-meta-label">Enseignant</p>
                                    <p class="portal-record-meta-value">{{ $grade->teacher?->name ?? '-' }}</p>
                                </div>
                                <div class="portal-record-meta-item">
                                    <p class="portal-record-meta-label">Score</p>
                                    <p class="portal-record-meta-value">{{ number_format((float) $grade->score, 2) }} / {{ $grade->max_score ?? '-' }}</p>
                                </div>
                            </div>
                            @if(!empty($grade->comment))
                                <p class="mt-3 text-sm text-slate-600">{{ $grade->comment }}</p>
                            @endif
                        </article>
                    @empty
                        <div class="student-empty">Aucune note disponible.</div>
                    @endforelse
                </div>

                <div class="mt-5">
                    {{ $grades->links() }}
                </div>
            </section>
        </div>

        <aside class="space-y-4">
            <section class="student-panel">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Moyenne generale</p>
                <p class="mt-3 text-4xl font-semibold tracking-tight text-slate-950">{{ number_format($overallAverage, 2) }}%</p>
                <p class="mt-2 text-sm text-slate-600">Calculee sur les notes visibles dans votre espace parent.</p>
            </section>
        </aside>
    </section>
</x-parent-layout>
