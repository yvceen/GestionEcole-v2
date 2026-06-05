<x-parent-layout title="Mes rendez-vous" subtitle="Suivez vos demandes, les dates confirmees et les retours de l'administration.">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-semibold text-slate-950">Demandes de rendez-vous</h2>
            <p class="mt-1 text-sm text-slate-500">Chaque demande garde son statut et les notes de suivi.</p>
        </div>

        <x-ui.button :href="route('parent.appointments.create')" variant="primary">
            Nouvelle demande
        </x-ui.button>
    </div>

    @if(session('success'))
        <div class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
            {{ session('success') }}
        </div>
    @endif

    <section class="mt-6 space-y-4">
        @forelse($appointments as $appointment)
            @php
                $status = $appointment->normalized_status;
                $badgeClasses = match ($status) {
                    'approved' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
                    'completed' => 'border-sky-200 bg-sky-50 text-sky-800',
                    'rejected' => 'border-rose-200 bg-rose-50 text-rose-800',
                    default => 'border-amber-200 bg-amber-50 text-amber-800',
                };
            @endphp
            <article class="student-panel">
                <div class="portal-record-card-head">
                    <div class="min-w-0">
                        <p class="student-eyebrow">Rendez-vous</p>
                        <h3 class="mt-2 text-lg font-semibold text-slate-950">{{ $appointment->title }}</h3>
                        <p class="mt-2 text-sm text-slate-500">
                            {{ optional($appointment->scheduled_for)->format('d/m/Y H:i') ?? 'Non planifie' }}
                            @if($appointment->student)
                                <span class="mx-2 text-slate-300">|</span>
                                {{ $appointment->student->full_name }}{{ $appointment->student->classroom?->name ? ' - ' . $appointment->student->classroom->name : '' }}
                            @endif
                        </p>
                    </div>
                    <span class="inline-flex rounded-full border px-3 py-1 text-xs font-semibold {{ $badgeClasses }}">
                        {{ ucfirst($status) }}
                    </span>
                </div>

                @if($appointment->message)
                    <p class="mt-4 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $appointment->message }}</p>
                @endif

                <div class="portal-record-meta">
                    <div class="portal-record-meta-item">
                        <p class="portal-record-meta-label">Creation</p>
                        <p class="portal-record-meta-value">{{ optional($appointment->created_at)->format('d/m/Y H:i') ?? 'N/A' }}</p>
                    </div>
                    <div class="portal-record-meta-item">
                        <p class="portal-record-meta-label">Statut</p>
                        <p class="portal-record-meta-value">{{ ucfirst($status) }}</p>
                    </div>
                </div>

                @if($appointment->admin_notes)
                    <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Note administration</p>
                        <p class="mt-2 whitespace-pre-line">{{ $appointment->admin_notes }}</p>
                    </div>
                @endif
            </article>
        @empty
            <div class="student-empty text-center">
                <p class="text-base font-semibold text-slate-900">Aucun rendez-vous pour le moment.</p>
                <p class="mt-2 text-sm text-slate-500">Envoyez votre premiere demande pour lancer le suivi avec l'equipe scolaire.</p>
            </div>
        @endforelse
    </section>

    <div class="mt-5">
        {{ $appointments->links() }}
    </div>
</x-parent-layout>
