@php
    $selectedClassroomId = $selectedClassroom?->id;
    $selectedStudentId = $selectedStudent?->id;

    $statusLabel = static function (string $value): string {
        return match ($value) {
            \App\Models\Attendance::STATUS_PRESENT => 'Present',
            \App\Models\Attendance::STATUS_ABSENT => 'Absent',
            \App\Models\Attendance::STATUS_LATE => 'En retard',
            default => ucfirst($value),
        };
    };

    $statusVariant = static function (string $value): string {
        return match ($value) {
            \App\Models\Attendance::STATUS_PRESENT => 'success',
            \App\Models\Attendance::STATUS_ABSENT => 'danger',
            \App\Models\Attendance::STATUS_LATE => 'warning',
            default => 'info',
        };
    };
@endphp

<section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="app-stat-card">
        <p class="app-stat-label">Enregistrements</p>
        <p class="app-stat-value">{{ $summary['total'] }}</p>
        <p class="app-stat-meta">Toutes presences, absences et retards sur la selection.</p>
    </div>

    <div class="app-stat-card">
        <p class="app-stat-label">Presents</p>
        <p class="app-stat-value text-emerald-700">{{ $summary['present'] }}</p>
        <p class="app-stat-meta">Eleves marques presents.</p>
    </div>

    <div class="app-stat-card">
        <p class="app-stat-label">Absences</p>
        <p class="app-stat-value text-rose-700">{{ $summary['absent'] }}</p>
        <p class="app-stat-meta">Suivi des absences a surveiller.</p>
    </div>

    <div class="app-stat-card">
        <p class="app-stat-label">Retards</p>
        <p class="app-stat-value text-amber-700">{{ $summary['late'] }}</p>
        <p class="app-stat-meta">Eleves arrives apres l'appel.</p>
    </div>
</section>

<x-ui.card
    title="Filtres de suivi"
    subtitle="Affinez l'analyse par classe, eleve, statut ou plage de dates sans sortir du contexte de l'etablissement."
>
    <form method="GET" class="grid gap-3 xl:grid-cols-[minmax(0,1.25fr)_minmax(0,1.1fr)_180px_180px_180px_auto_auto]">
        <select name="classroom_id" class="app-input">
            <option value="">Toutes les classes</option>
            @foreach($classrooms as $classroom)
                <option value="{{ $classroom->id }}" @selected((string) $selectedClassroomId === (string) $classroom->id)>
                    {{ $classroom->name }}
                    @if($classroom->level?->name)
                        - {{ $classroom->level->name }}
                    @endif
                </option>
            @endforeach
        </select>

        <select name="student_id" class="app-input">
            <option value="">Tous les eleves</option>
            @foreach($students as $student)
                <option value="{{ $student->id }}" @selected((string) $selectedStudentId === (string) $student->id)>
                    {{ $student->full_name }}
                </option>
            @endforeach
        </select>

        <select name="status" class="app-input">
            <option value="">Tous les statuts</option>
            @foreach(\App\Models\Attendance::statuses() as $attendanceStatus)
                <option value="{{ $attendanceStatus }}" @selected($status === $attendanceStatus)>
                    {{ $statusLabel($attendanceStatus) }}
                </option>
            @endforeach
        </select>

        <input type="date" name="date_from" value="{{ $dateFrom?->format('Y-m-d') }}" class="app-input">
        <input type="date" name="date_to" value="{{ $dateTo?->format('Y-m-d') }}" class="app-input">

        <x-ui.button type="submit" variant="primary">
            Filtrer
        </x-ui.button>

        <x-ui.button :href="url()->current()" variant="secondary">
            Reinitialiser
        </x-ui.button>

        @if(request()->routeIs('school-life.attendance.*') && \Illuminate\Support\Facades\Route::has('school-life.attendance.export'))
            <x-ui.button :href="route('school-life.attendance.export', request()->query())" variant="outline">
                Exporter Excel
            </x-ui.button>
        @endif
    </form>
</x-ui.card>

<section class="grid gap-6 xl:grid-cols-[minmax(0,1.05fr)_minmax(0,0.95fr)]">
    <x-ui.card title="Vue par classe" subtitle="Reperez rapidement les classes avec le plus d'absences ou de retards.">
        <div class="space-y-3">
            @forelse($classSummary as $row)
                <div class="rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-950">{{ $row->classroom?->name ?? 'Classe' }}</p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ (int) $row->total_records }} enregistrement(s)
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <x-ui.badge variant="danger">Absences : {{ (int) $row->absences_count }}</x-ui.badge>
                            <x-ui.badge variant="warning">Retards : {{ (int) $row->late_count }}</x-ui.badge>
                        </div>
                    </div>
                </div>
            @empty
                <div class="student-empty px-5 py-8">
                    Aucun regroupement par classe pour la selection en cours.
                </div>
            @endforelse
        </div>
    </x-ui.card>

    <x-ui.card title="Vue par eleve" subtitle="Identifiez les eleves qui concentrent le plus d'absences ou de retards.">
        <div class="space-y-3">
            @forelse($studentSummary as $row)
                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-950">{{ $row->student?->full_name ?? 'Eleve' }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $row->classroom?->name ?? 'Classe non renseignee' }}</p>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <x-ui.badge variant="danger">Absences : {{ (int) $row->absences_count }}</x-ui.badge>
                            <x-ui.badge variant="warning">Retards : {{ (int) $row->late_count }}</x-ui.badge>
                        </div>
                    </div>
                </div>
            @empty
                <div class="student-empty px-5 py-8">
                    Aucun eleve a signaler sur cette plage.
                </div>
            @endforelse
        </div>
    </x-ui.card>
</section>

<x-ui.card title="Registre detaille" subtitle="Lecture ligne a ligne, avec la classe, le statut, la date, les heures de scan et la source de saisie.">
    <div class="overflow-x-auto rounded-2xl border border-slate-200">
        <table class="app-table min-w-[1180px]">
            <thead>
                <tr>
                    <th>Eleve</th>
                    <th>Classe</th>
                    <th>Date</th>
                    <th>Statut</th>
                    <th>Entree</th>
                    <th>Sortie</th>
                    <th>Source</th>
                    <th>Note</th>
                    <th>Enregistre par</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $attendance)
                    <tr>
                        <td>
                            <div class="font-semibold text-slate-950">{{ $attendance->student?->full_name ?? '-' }}</div>
                        </td>
                        <td>{{ $attendance->classroom?->name ?? '-' }}</td>
                        <td>{{ $attendance->date?->format('d/m/Y') ?? '-' }}</td>
                        <td>
                            <x-ui.badge :variant="$statusVariant((string) $attendance->status)">
                                {{ $statusLabel((string) $attendance->status) }}
                            </x-ui.badge>
                        </td>
                        <td>{{ $attendance->check_in_at?->format('H:i') ?? '-' }}</td>
                        <td>{{ $attendance->check_out_at?->format('H:i') ?? '-' }}</td>
                        <td>
                            @php
                                $sourceLabel = match ((string) $attendance->recorded_via) {
                                    \App\Models\Attendance::RECORDED_VIA_QR => 'QR scan',
                                    \App\Models\Attendance::RECORDED_VIA_MANUAL => 'Correction',
                                    default => 'Appel enseignant',
                                };
                            @endphp
                            <div class="text-sm font-medium text-slate-700">{{ $sourceLabel }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ $attendance->scannedBy?->name ?? $attendance->markedBy?->name ?? '-' }}</div>
                        </td>
                        <td class="max-w-[260px]">
                            <span class="break-words">{{ $attendance->note ?: '-' }}</span>
                        </td>
                        <td>{{ $attendance->markedBy?->name ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-5 py-10 text-center text-sm text-slate-500">
                            Aucun enregistrement de presence ne correspond a ces filtres.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">
        {{ $records->links() }}
    </div>
</x-ui.card>
