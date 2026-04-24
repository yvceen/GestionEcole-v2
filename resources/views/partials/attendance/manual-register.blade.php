@php
    $layoutRoutePrefix = $routePrefix ?? 'teacher.attendance';
    $scanRoute = $scanRoute ?? route('attendance.scan.page');
    $manualRoute = $manualRoute ?? route($layoutRoutePrefix . '.index');
    $storeRoute = $storeRoute ?? route($layoutRoutePrefix . '.store');
    $historyRouteName = $historyRouteName ?? ($layoutRoutePrefix . '.index');
    $pageTitle = $pageTitle ?? "Registre d'appel";
    $pageSubtitle = $pageSubtitle ?? "Saisissez les presences, absences et retards par classe et par date, puis retrouvez vos derniers appels en un coup d'oeil.";
    $modeBadge = $modeBadge ?? 'Saisie manuelle';
    $helperCopy = $helperCopy ?? "Si un appel existe deja pour cette classe et cette date, la saisie est rechargee et sera mise a jour.";
    $saveLabel = $saveLabel ?? (!empty($attendanceByStudentId) ? 'Mettre a jour le registre' : 'Enregistrer le registre');
@endphp

<section class="grid gap-6 xl:grid-cols-[minmax(0,1.2fr)_360px]">
    <div class="space-y-6">
        <x-ui.card
            title="Mode de pointage"
            subtitle="Choisissez le mode adapte a votre situation sans desactiver le scan existant."
        >
            <div class="flex flex-col gap-3 sm:flex-row">
                <x-ui.button :href="$scanRoute" variant="secondary">
                    Scan
                </x-ui.button>
                <x-ui.button :href="$manualRoute" variant="primary">
                    Saisie manuelle
                </x-ui.button>
            </div>
        </x-ui.card>

        <x-ui.card
            title="Preparation de l'appel"
            subtitle="Choisissez une classe et une date, puis ouvrez le registre pour saisir ou corriger les statuts."
        >
            <form method="GET" action="{{ $manualRoute }}" class="grid gap-3 lg:grid-cols-[180px_minmax(0,1fr)_auto_auto]">
                <div class="app-field">
                    <label class="app-label" for="attendance-date">Date</label>
                    <input id="attendance-date" type="date" name="date" value="{{ $date }}" class="app-input">
                </div>

                <div class="app-field">
                    <label class="app-label" for="attendance-classroom">Classe</label>
                    <select id="attendance-classroom" name="classroom_id" class="app-input">
                        <option value="">Choisir une classe</option>
                        @foreach($classrooms as $classroom)
                            <option value="{{ $classroom->id }}" @selected((string) $classroomId === (string) $classroom->id)>
                                {{ $classroom->name }}
                                @if($classroom->level?->name)
                                    - {{ $classroom->level->name }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <x-ui.button type="submit" variant="primary" class="lg:self-end">
                    Afficher le registre
                </x-ui.button>

                <x-ui.button :href="$manualRoute" variant="secondary" class="lg:self-end">
                    Reinitialiser
                </x-ui.button>
            </form>
        </x-ui.card>

        <x-ui.card
            :title="$pageTitle"
            :subtitle="$selectedClassroom ? 'Saisie pour '.$selectedClassroom->name.' le '.\Carbon\Carbon::parse($date)->format('d/m/Y') : 'Selectionnez une classe pour charger la liste des eleves.'"
        >
            @if(!$selectedClassroom)
                <div class="student-empty px-5 py-8">
                    Choisissez une classe pour afficher le registre d'appel manuel.
                </div>
            @elseif($students->isEmpty())
                <div class="student-empty px-5 py-8">
                    Aucun eleve n'est actuellement rattache a cette classe.
                </div>
            @else
                <form method="POST" action="{{ $storeRoute }}" class="space-y-5" id="manual-attendance-form">
                    @csrf
                    <input type="hidden" name="date" value="{{ $date }}">
                    <input type="hidden" name="classroom_id" value="{{ $selectedClassroom->id }}">

                    <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ $modeBadge }}</p>
                            <p class="mt-1 text-sm text-slate-500">Utilisez “Tout le monde present” puis ne corrigez que les exceptions.</p>
                        </div>
                        <div class="flex flex-col gap-3 sm:flex-row">
                            <button type="button" class="app-button-outline" data-mark-all-present>
                                Tout le monde present
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto rounded-2xl border border-slate-200">
                        <table class="app-table min-w-[920px]">
                            <thead>
                                <tr>
                                    <th>Eleve</th>
                                    <th>Statut</th>
                                    <th>Observation</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($students as $student)
                                    @php
                                        $row = $attendanceByStudentId[$student->id] ?? null;
                                        $status = old("attendance.$student->id.status", $row->status ?? \App\Models\Attendance::STATUS_PRESENT);
                                        $note = old("attendance.$student->id.note", $row->note ?? '');
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="font-semibold text-slate-950">{{ $student->full_name }}</div>
                                            @if($row)
                                                <div class="mt-1 text-xs text-slate-500">
                                                    Derniere saisie : {{ $row->updated_at?->format('d/m/Y H:i') ?? $row->created_at?->format('d/m/Y H:i') ?? '-' }}
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="grid gap-2 sm:grid-cols-3">
                                                @foreach(\App\Models\Attendance::statuses() as $attendanceStatus)
                                                    @php
                                                        $checked = $status === $attendanceStatus;
                                                        $tone = match ($attendanceStatus) {
                                                            \App\Models\Attendance::STATUS_PRESENT => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                                                            \App\Models\Attendance::STATUS_ABSENT => 'border-rose-200 bg-rose-50 text-rose-700',
                                                            default => 'border-amber-200 bg-amber-50 text-amber-700',
                                                        };
                                                    @endphp
                                                    <label class="flex cursor-pointer items-center gap-2 rounded-2xl border px-3 py-3 text-sm font-semibold {{ $checked ? $tone : 'border-slate-200 bg-white text-slate-600' }}">
                                                        <input
                                                            type="radio"
                                                            name="attendance[{{ $student->id }}][status]"
                                                            value="{{ $attendanceStatus }}"
                                                            class="h-4 w-4 border-slate-300 text-sky-700 focus:ring-sky-200"
                                                            @checked($checked)
                                                            @if($attendanceStatus === \App\Models\Attendance::STATUS_PRESENT) data-present-radio="true" @endif
                                                        >
                                                        <span>
                                                            {{ match ($attendanceStatus) {
                                                                \App\Models\Attendance::STATUS_PRESENT => 'Present',
                                                                \App\Models\Attendance::STATUS_ABSENT => 'Absent',
                                                                default => 'En retard',
                                                            } }}
                                                        </span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td>
                                            <input
                                                type="text"
                                                name="attendance[{{ $student->id }}][note]"
                                                value="{{ $note }}"
                                                class="app-input"
                                                placeholder="Exemple : justificatif medical, transport, retard ..."
                                            >
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p class="app-hint">{{ $helperCopy }}</p>

                        <x-ui.button type="submit" variant="primary">
                            {{ $saveLabel }}
                        </x-ui.button>
                    </div>
                </form>
            @endif
        </x-ui.card>
    </div>

    <aside class="space-y-6">
        <x-ui.card title="Repere rapide" subtitle="Ce bloc vous aide a savoir ou vous en etes sur la journee selectionnee.">
            <div class="space-y-3">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                    <p class="app-stat-label">Classe chargee</p>
                    <p class="mt-2 text-lg font-semibold text-slate-950">{{ $selectedClassroom?->name ?? 'Aucune classe' }}</p>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                    <p class="app-stat-label">Date de saisie</p>
                    <p class="mt-2 text-lg font-semibold text-slate-950">{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</p>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                    <p class="app-stat-label">Eleves dans le registre</p>
                    <p class="mt-2 text-lg font-semibold text-slate-950">{{ $students->count() }}</p>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card title="Derniers appels" subtitle="Historique recent des appels deja saisis, avec absences et retards.">
            <div class="space-y-3">
                @forelse($sessionHistory as $session)
                    <a
                        href="{{ route($historyRouteName, ['classroom_id' => $session['classroom_id'], 'date' => $session['date']->toDateString()]) }}"
                        class="block rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm transition hover:border-slate-300 hover:bg-slate-50"
                    >
                        <div class="flex flex-col gap-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-950">{{ $session['classroom_name'] }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $session['date']->format('d/m/Y') }}</p>
                                </div>
                                <span class="app-badge app-badge-info">{{ $session['total_students'] }} eleves</span>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <span class="app-badge app-badge-danger">Absents : {{ $session['absent_count'] }}</span>
                                <span class="app-badge app-badge-warning">Retards : {{ $session['late_count'] }}</span>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="student-empty px-5 py-8">
                        Aucun appel recent n'a encore ete enregistre.
                    </div>
                @endforelse
            </div>
        </x-ui.card>
    </aside>
</section>

@push('scripts')
    <script>
        (function () {
            const trigger = document.querySelector('[data-mark-all-present]');
            if (!trigger) {
                return;
            }

            trigger.addEventListener('click', function () {
                document.querySelectorAll('input[data-present-radio="true"]').forEach(function (input) {
                    input.checked = true;
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                });
            });
        })();
    </script>
@endpush
