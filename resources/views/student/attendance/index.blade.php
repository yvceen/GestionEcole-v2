<x-student-layout title="Mes presences" subtitle="Visualisez rapidement vos presences, absences et retards, avec des filtres simples par date et statut.">
    <section class="grid gap-4 md:grid-cols-4">
        <article class="student-kpi">
            <p class="student-kpi-label">Total</p>
            <p class="student-kpi-value">{{ $summary['total'] }}</p>
        </article>
        <article class="student-kpi">
            <p class="student-kpi-label">Presents</p>
            <p class="mt-3 text-3xl font-semibold tracking-tight text-emerald-700">{{ $summary['present'] }}</p>
        </article>
        <article class="student-kpi">
            <p class="student-kpi-label">Absents</p>
            <p class="mt-3 text-3xl font-semibold tracking-tight text-rose-700">{{ $summary['absent'] }}</p>
        </article>
        <article class="student-kpi">
            <p class="student-kpi-label">Retards</p>
            <p class="mt-3 text-3xl font-semibold tracking-tight text-amber-700">{{ $summary['late'] }}</p>
        </article>
    </section>

    <section class="portal-filter-bar mt-6">
        <form method="GET" data-loading-label="Filtrage des presences..." class="portal-filter-grid md:grid-cols-[180px_180px_180px_auto]">
            <input type="date" name="date_from" value="{{ $dateFrom?->format('Y-m-d') }}" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
            <input type="date" name="date_to" value="{{ $dateTo?->format('Y-m-d') }}" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
            <select name="status" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                <option value="">Tous les statuts</option>
                <option value="present" @selected($status === 'present')>Present</option>
                <option value="absent" @selected($status === 'absent')>Absent</option>
                <option value="late" @selected($status === 'late')>En retard</option>
            </select>
            <div class="portal-filter-actions">
                <button class="app-button-primary rounded-2xl px-4 py-3">
                    Filtrer
                </button>
                @if($dateFrom || $dateTo || $status)
                    <a href="{{ route('student.attendance.index') }}" class="app-button-secondary rounded-2xl px-4 py-3">
                        Reinitialiser
                    </a>
                @endif
            </div>
        </form>
    </section>

    <section class="student-panel mt-6">
        <div class="hidden overflow-x-auto md:block">
            <table class="min-w-full text-sm">
                <thead class="border-b border-slate-200 text-left text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">
                    <tr>
                        <th class="px-3 py-3">Date</th>
                        <th class="px-3 py-3">Statut</th>
                        <th class="px-3 py-3">Enregistre par</th>
                        <th class="px-3 py-3">Note</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($attendances as $attendance)
                        @php
                            $tone = match ($attendance->status) {
                                'present' => 'bg-emerald-100 text-emerald-700',
                                'absent' => 'bg-rose-100 text-rose-700',
                                'late' => 'bg-amber-100 text-amber-700',
                                default => 'bg-slate-100 text-slate-700',
                            };
                        @endphp
                        <tr>
                            <td class="px-3 py-4 font-semibold text-slate-950">{{ $attendance->date?->format('d/m/Y') ?? '-' }}</td>
                            <td class="px-3 py-4">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $tone }}">{{ strtoupper((string) $attendance->status) }}</span>
                            </td>
                            <td class="px-3 py-4 text-slate-600">{{ $attendance->markedBy?->name ?? '-' }}</td>
                            <td class="px-3 py-4 text-slate-600">{{ $attendance->note ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-3 py-10 text-center text-sm text-slate-500">Aucune presence enregistree.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="portal-record-stack md:hidden">
            @forelse($attendances as $attendance)
                @php
                    $tone = match ($attendance->status) {
                        'present' => 'bg-emerald-100 text-emerald-700',
                        'absent' => 'bg-rose-100 text-rose-700',
                        'late' => 'bg-amber-100 text-amber-700',
                        default => 'bg-slate-100 text-slate-700',
                    };
                @endphp
                <article class="portal-record-card">
                    <div class="portal-record-card-head">
                        <div class="min-w-0">
                            <p class="portal-record-title">{{ $attendance->date?->format('d/m/Y') ?? '-' }}</p>
                            <p class="portal-record-subtitle">Historique de presence</p>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $tone }}">
                            {{ $attendance->status === 'late' ? 'En retard' : strtoupper((string) $attendance->status) }}
                        </span>
                    </div>
                    <div class="portal-record-meta">
                        <div class="portal-record-meta-item">
                            <p class="portal-record-meta-label">Enregistre par</p>
                            <p class="portal-record-meta-value">{{ $attendance->markedBy?->name ?? '-' }}</p>
                        </div>
                        <div class="portal-record-meta-item">
                            <p class="portal-record-meta-label">Note</p>
                            <p class="portal-record-meta-value">{{ $attendance->note ?: 'Aucune note' }}</p>
                        </div>
                    </div>
                </article>
            @empty
                <div class="student-empty">Aucune presence enregistree.</div>
            @endforelse
        </div>

        <div class="mt-5">
            {{ $attendances->links() }}
        </div>
    </section>
</x-student-layout>
