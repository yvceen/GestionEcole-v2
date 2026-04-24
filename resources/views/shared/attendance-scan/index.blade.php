@php
    $layout = $user->role === \App\Models\User::ROLE_TEACHER ? 'teacher-layout' : 'school-life-layout';
    $scanTitle = 'Scan QR des presences';
    $scanSubtitle = 'Scan mobile simple a l entree, avec retour immediat sur le statut present ou en retard.';
@endphp

@include('partials.attendance.qr-scanner-scripts')

<x-dynamic-component :component="$layout" :title="$scanTitle" :subtitle="$scanSubtitle">
    <x-ui.page-header
        title="Scan d entree"
        subtitle="Les enseignants et la vie scolaire peuvent scanner un QR eleve. Un seul enregistrement est garde par jour."
    />

    <div
        x-data="createAttendanceQrScanner({
            endpoint: @js(route('api.attendance.scan')),
            requestKey: 'qr_token',
            csrfToken: @js(csrf_token()),
            onSuccess(payload) {
                this.result = {
                    ...payload,
                    variant: payload.status === 'late' ? 'late' : 'present',
                };

                if (payload.status === 'late') {
                    this.playLateSound();
                } else {
                    this.playPresentSound();
                }
            },
            onError(message) {
                this.result = {
                    variant: 'error',
                    status: 'error',
                    student_name: 'Scan impossible',
                    message,
                };
                this.playErrorSound();
            },
        })"
        x-init="initController()"
        class="grid gap-6 xl:grid-cols-[minmax(0,1.05fr)_minmax(320px,0.95fr)]"
    >
        <x-ui.card title="Scanner" subtitle="Camera mobile si disponible, ou saisie manuelle du QR code.">
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
                    <input type="text" x-model="scanCode" class="app-input" placeholder="Exemple : MYEDU:STUDENT:STD-ABCD2345EFGH">
                    <x-ui.button type="submit" variant="primary" x-bind:disabled="busy">
                        Scanner / Valider
                    </x-ui.button>
                </form>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                    <p class="font-semibold text-slate-900">Mode one-shot</p>
                    <p class="mt-1 leading-6">
                        Des qu un QR code est lu, le scan se fige, la camera s arrete et la validation part une seule fois.
                        Utilisez <span class="font-semibold">Redemarrer le scan</span> pour reprendre.
                    </p>
                    <p class="mt-2 text-xs text-slate-500" x-show="cameraError" x-text="cameraError"></p>
                </div>
            </div>
        </x-ui.card>

        <div class="space-y-6">
            <x-ui.card title="Dernier scan" subtitle="Nom de l eleve, statut et message de confirmation.">
                <template x-if="result">
                    <div class="rounded-[24px] border px-5 py-5 shadow-sm transition" :class="resultCardClass()">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold" :class="resultBadgeClass()" x-text="resultLabel()"></span>
                            <span x-show="result.duplicate" class="app-badge app-badge-info">Deja pointe</span>
                        </div>
                        <p class="mt-4 text-lg font-semibold" x-text="result.student_name || 'Scan QR'"></p>
                        <p class="mt-2 text-sm leading-6 opacity-90" x-text="result.message"></p>
                    </div>
                </template>

                <template x-if="!result">
                    <div class="student-empty">
                        Aucun scan realise pour le moment.
                    </div>
                </template>
            </x-ui.card>

            <x-ui.card title="Scans recents" subtitle="Derniers pointages de la journee.">
                <div class="space-y-3">
                    @forelse($recentScans as $attendance)
                        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="font-semibold text-slate-950">{{ $attendance->student?->full_name ?? '-' }}</p>
                                    <p class="mt-1 text-sm text-slate-600">{{ $attendance->student?->classroom?->name ?? '-' }}</p>
                                </div>
                                <div class="text-right">
                                    <x-ui.badge :variant="$attendance->status === 'late' ? 'warning' : 'success'">
                                        {{ $attendance->status === 'late' ? 'En retard' : 'Present' }}
                                    </x-ui.badge>
                                    <p class="mt-2 text-xs text-slate-500">{{ $attendance->check_in_at?->format('H:i') ?? '-' }}</p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="student-empty">Aucun scan enregistre aujourd hui.</div>
                    @endforelse
                </div>
            </x-ui.card>
        </div>
    </div>
</x-dynamic-component>
