@include('partials.attendance.qr-scanner-scripts')

<x-school-life-layout title="Scan QR presences" subtitle="Enregistrez rapidement les arrivees et sorties a partir des cartes eleves, avec camera si disponible et saisie manuelle si besoin.">
    <x-ui.page-header
        title="Scan arrivee / sortie"
        subtitle="Premier scan du jour : arrivee. Scan suivant : sortie. Les corrections restent possibles sur les derniers pointages."
    >
        <x-slot name="actions">
            <x-ui.button :href="route('school-life.attendance.index')" variant="secondary">
                Voir le monitoring complet
            </x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.card title="Contexte du scan" subtitle="Choisissez la date et, si besoin, une classe pour limiter les derniers pointages affiches.">
        <form method="GET" class="grid gap-3 lg:grid-cols-[220px_220px_auto_auto]">
            <input type="date" name="date" value="{{ $date }}" class="app-input">
            <select name="classroom_id" class="app-input">
                <option value="">Toutes les classes</option>
                @foreach($classrooms as $classroom)
                    <option value="{{ $classroom->id }}" @selected((string) $classroomId === (string) $classroom->id)>
                        {{ $classroom->name }}
                    </option>
                @endforeach
            </select>
            <x-ui.button type="submit" variant="primary">Appliquer</x-ui.button>
            <x-ui.button :href="route('school-life.qr-scan.index')" variant="secondary">Reinitialiser</x-ui.button>
        </form>
    </x-ui.card>

    <div
        x-data="createAttendanceQrScanner({
            endpoint: @js(route('school-life.qr-scan.store')),
            requestKey: 'code',
            csrfToken: @js(csrf_token()),
            records: @js($records->map(fn ($attendance) => [
                'id' => $attendance->id,
                'student_name' => $attendance->student?->full_name ?? 'Eleve',
                'classroom_name' => $attendance->student?->classroom?->name ?? '-',
                'status_label' => match ((string) $attendance->status) {
                    \App\Models\Attendance::STATUS_PRESENT => 'Present',
                    \App\Models\Attendance::STATUS_ABSENT => 'Absent',
                    \App\Models\Attendance::STATUS_LATE => 'En retard',
                    default => ucfirst((string) $attendance->status),
                },
                'check_in_at' => optional($attendance->check_in_at)->format('H:i'),
                'check_out_at' => optional($attendance->check_out_at)->format('H:i'),
                        'edit_url' => route('school-life.qr-scan.records.edit', $attendance),
            ])),
            onSuccess(payload) {
                this.result = {
                    ...payload,
                    variant: payload.record?.status === 'late' ? 'late' : 'present',
                };

                if (payload.record) {
                    const record = {
                        ...payload.record,
                        edit_url: @js(route('school-life.qr-scan.records.edit', ['attendance' => '__ID__'])).replace('__ID__', payload.record.id),
                    };
                    this.records = [record, ...this.records.filter((item) => item.id !== record.id)].slice(0, 20);
                }

                if (payload.record?.status === 'late') {
                    this.playLateSound();
                } else {
                    this.playPresentSound();
                }
            },
            onError(message) {
                this.result = {
                    variant: 'error',
                    status: 'error',
                    record: {
                        student_name: 'Scan impossible',
                        classroom_name: '',
                        status_label: 'Erreur',
                        check_in_at: null,
                        check_out_at: null,
                    },
                    message,
                };
                this.playErrorSound();
            },
        })"
        x-init="initController()"
        class="grid gap-6 xl:grid-cols-[minmax(0,1.05fr)_minmax(320px,0.95fr)]"
    >
        <x-ui.card title="Zone de scan" subtitle="Camera sur navigateur compatible, sinon saisissez le code de la carte manuellement.">
            <div class="space-y-5">
                <div class="overflow-hidden rounded-[28px] border border-slate-200 bg-slate-950">
                    <div x-ref="reader" data-qr-reader class="aspect-[4/3] w-full"></div>
                </div>

                <div class="flex flex-wrap gap-3">
                    <x-ui.button type="button" variant="primary" x-on:click="startScanner()" x-bind:disabled="scanning || busy || isProcessing">
                        <span x-text="scanning ? 'Camera active' : 'Activer la camera'"></span>
                    </x-ui.button>
                    <x-ui.button type="button" variant="secondary" x-on:click="restartScanner()" x-bind:disabled="busy || isProcessing">
                        Redemarrer le scan
                    </x-ui.button>
                    <x-ui.button type="button" variant="secondary" x-on:click="stopScanner({ preserveLock: true })" x-bind:disabled="!scanning">
                        Arreter
                    </x-ui.button>
                </div>

                <form class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto]" x-on:submit.prevent="submitCode()">
                    <input type="text" x-model="scanCode" class="app-input" placeholder="Exemple : STD-ABCD2345EFGH">
                    <x-ui.button type="submit" variant="primary" x-bind:disabled="busy">
                        Valider le code
                    </x-ui.button>
                </form>

                <p class="text-xs leading-5 text-slate-500">
                    Le QR eleve contient uniquement un code securise. Si le scan camera n est pas disponible, vous pouvez aussi saisir le code de la carte manuellement.
                </p>
                <p class="text-xs leading-5 text-slate-500" x-show="cameraError" x-text="cameraError"></p>
            </div>
        </x-ui.card>

        <div class="space-y-6">
            <x-ui.card title="Resultat du dernier scan" subtitle="Retour visuel immediat pour la vie scolaire.">
                <template x-if="result">
                    <div class="rounded-[24px] border px-5 py-5 shadow-sm transition" :class="resultCardClass()">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold" :class="resultBadgeClass()" x-text="result.action === 'check_out' ? 'Sortie' : resultLabel()"></span>
                            <span
                                class="app-badge"
                                :class="result.record?.status === 'late' ? 'app-badge-warning' : 'app-badge-success'"
                                x-text="result.record?.status_label || resultLabel()"
                            ></span>
                        </div>
                        <p class="mt-4 text-lg font-semibold" x-text="result.record?.student_name || 'Scan QR'"></p>
                        <p class="mt-1 text-sm opacity-80" x-text="result.record?.classroom_name || ''"></p>
                        <p class="mt-3 text-sm leading-6 opacity-90" x-text="result.message"></p>
                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <div class="rounded-2xl bg-white px-4 py-3 text-sm text-slate-600">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Entree</p>
                                <p class="mt-2 font-semibold text-slate-900" x-text="result.record?.check_in_at || '-'"></p>
                            </div>
                            <div class="rounded-2xl bg-white px-4 py-3 text-sm text-slate-600">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Sortie</p>
                                <p class="mt-2 font-semibold text-slate-900" x-text="result.record?.check_out_at || '-'"></p>
                            </div>
                        </div>
                    </div>
                </template>

                <template x-if="!result">
                    <div class="student-empty">
                        Aucun scan effectue pour le moment sur cette page.
                    </div>
                </template>
            </x-ui.card>

            <x-ui.card title="Derniers pointages" subtitle="Liste courte des arrivees et sorties du jour, avec acces direct a la correction.">
                <div class="space-y-3">
                    <template x-for="record in records" :key="record.id">
                        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <p class="font-semibold text-slate-950" x-text="record.student_name"></p>
                                    <p class="mt-1 text-sm text-slate-600" x-text="record.classroom_name"></p>
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700" x-text="record.status_label"></span>
                                    <a :href="record.edit_url" class="app-button-secondary px-3 py-2 text-sm">Corriger</a>
                                </div>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-3 text-xs font-medium text-slate-500">
                                <span>Entree : <strong class="text-slate-800" x-text="record.check_in_at || '-'"></strong></span>
                                <span>Sortie : <strong class="text-slate-800" x-text="record.check_out_at || '-'"></strong></span>
                            </div>
                        </div>
                    </template>

                    <template x-if="records.length === 0">
                        <div class="student-empty">Aucun pointage pour cette date.</div>
                    </template>
                </div>
            </x-ui.card>
        </div>
    </div>
</x-school-life-layout>
