<x-parent-layout title="Mes rendez-vous">
    <div class="mx-auto max-w-5xl px-4 py-6 sm:px-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">Mes rendez-vous</h1>
                <p class="mt-1 text-sm text-slate-600">Suivez vos demandes, les dates confirmees et les retours de l administration.</p>
            </div>

            <a href="{{ route('parent.appointments.create') }}"
               class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-black">
                Nouvelle demande
            </a>
        </div>

        @if(session('success'))
            <div class="mt-5 rounded-3xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                {{ session('success') }}
            </div>
        @endif

        <div class="mt-6 space-y-4">
            @forelse($appointments as $appointment)
                @php
                    $status = $appointment->normalized_status;
                    $badgeClasses = match ($status) {
                        'approved' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                        'completed' => 'bg-sky-50 text-sky-800 border-sky-200',
                        'rejected' => 'bg-rose-50 text-rose-800 border-rose-200',
                        default => 'bg-amber-50 text-amber-800 border-amber-200',
                    };
                @endphp
                <article class="rounded-[28px] border border-slate-200 bg-white/85 p-5 shadow-sm">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="space-y-3">
                            <div class="flex flex-wrap items-center gap-3">
                                <h2 class="text-lg font-semibold text-slate-900">{{ $appointment->title }}</h2>
                                <span class="inline-flex rounded-full border px-3 py-1 text-xs font-semibold {{ $badgeClasses }}">
                                    {{ ucfirst($status) }}
                                </span>
                            </div>

                            <div class="flex flex-wrap gap-4 text-sm text-slate-600">
                                <span>Prevu le {{ optional($appointment->scheduled_for)->format('d/m/Y H:i') ?? 'Non planifie' }}</span>
                                @if($appointment->student)
                                    <span>{{ $appointment->student->full_name }}{{ $appointment->student->classroom?->name ? ' - ' . $appointment->student->classroom->name : '' }}</span>
                                @endif
                            </div>

                            @if($appointment->message)
                                <p class="whitespace-pre-line text-sm leading-6 text-slate-600">{{ $appointment->message }}</p>
                            @endif

                            @if($appointment->admin_notes)
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Note administration</p>
                                    <p class="mt-2 whitespace-pre-line">{{ $appointment->admin_notes }}</p>
                                </div>
                            @endif
                        </div>

                        <div class="text-sm text-slate-500">
                            Cree le {{ optional($appointment->created_at)->format('d/m/Y H:i') ?? 'N/A' }}
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-[28px] border border-dashed border-slate-300 bg-white/70 px-6 py-12 text-center">
                    <p class="text-lg font-semibold text-slate-900">Aucun rendez-vous pour le moment.</p>
                    <p class="mt-2 text-sm text-slate-500">Envoyez votre premiere demande pour lancer le suivi avec l equipe scolaire.</p>
                </div>
            @endforelse
        </div>

        <div class="mt-5">
            {{ $appointments->links() }}
        </div>
    </div>
</x-parent-layout>
