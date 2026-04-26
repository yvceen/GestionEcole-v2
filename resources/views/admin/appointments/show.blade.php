@php
    $layoutComponent = $layoutComponent ?? 'admin-layout';
    $routePrefix = $routePrefix ?? 'admin.appointments';
    $canEdit = $canEdit ?? true;
    $canDelete = $canDelete ?? true;
    $canApprove = $canApprove ?? true;
@endphp

<x-dynamic-component :component="$layoutComponent" title="Detail du rendez-vous">
    @php
        $status = $appointment->normalized_status ?? 'pending';
        $badgeVariant = match($status) {
            'approved' => 'success',
            'completed' => 'info',
            'rejected' => 'danger',
            default => 'warning',
        };
    @endphp

    <x-ui.page-header
        title="Detail du rendez-vous"
        subtitle="Consultez la demande parent, l enfant concerne et le suivi administratif sans quitter le module."
    >
        <x-slot name="actions">
            @if($canEdit)
                <x-ui.button :href="route($routePrefix . '.edit', $appointment)" variant="secondary">Modifier</x-ui.button>
            @endif
            <x-ui.button :href="route($routePrefix . '.index')" variant="ghost">Retour</x-ui.button>
            @if($status === 'pending' && $canApprove)
                <form method="POST" action="{{ route($routePrefix . '.approve', $appointment) }}">
                    @csrf
                    <x-ui.button type="submit" variant="outline">Approuver</x-ui.button>
                </form>
                <form method="POST" action="{{ route($routePrefix . '.reject', $appointment) }}">
                    @csrf
                    <x-ui.button type="submit" variant="danger">Refuser</x-ui.button>
                </form>
            @endif
        </x-slot>
    </x-ui.page-header>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1.4fr)_320px]">
        <x-ui.card title="Demande" subtitle="Lecture complete du rendez-vous parent.">
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Parent</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900">{{ optional($appointment->parentUser)->name ?? $appointment->parent_name ?? 'Parent inconnu' }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ optional($appointment->parentUser)->email ?? $appointment->parent_email ?? 'Email non renseigne' }}</p>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Telephone</p>
                    <p class="mt-2 text-sm text-slate-700">{{ $appointment->parent_phone ?: 'Non renseigne' }}</p>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Date prevue</p>
                    <p class="mt-2 text-sm text-slate-700">{{ optional($appointment->scheduled_for)->format('d/m/Y H:i') ?? 'Non planifiee' }}</p>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Statut</p>
                    <div class="mt-2">
                        <x-ui.badge :variant="$badgeVariant">{{ ucfirst($status) }}</x-ui.badge>
                    </div>
                </div>

                <div class="sm:col-span-2">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Titre</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900">{{ $appointment->title }}</p>
                </div>

                <div class="sm:col-span-2">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Enfant concerne</p>
                    <p class="mt-2 text-sm text-slate-700">
                        {{ $appointment->student?->full_name ?? 'Aucun enfant specifique' }}
                        @if($appointment->student?->classroom?->name)
                            - {{ $appointment->student->classroom->name }}
                        @endif
                    </p>
                </div>
            </div>

            <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Message</p>
                <p class="mt-3 whitespace-pre-line text-sm leading-7 text-slate-700">{{ $appointment->message ?: 'Aucun message complementaire.' }}</p>
            </div>
        </x-ui.card>

        <x-ui.card title="Suivi" subtitle="Repers utiles pour l equipe administrative.">
            <div class="space-y-4 text-sm text-slate-600">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Cree le</p>
                    <p class="mt-1 text-slate-900">{{ optional($appointment->created_at)->format('d/m/Y H:i') ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Derniere mise a jour</p>
                    <p class="mt-1 text-slate-900">{{ optional($appointment->updated_at)->format('d/m/Y H:i') ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Notes administration</p>
                    <p class="mt-1 whitespace-pre-line text-slate-900">{{ $appointment->admin_notes ?: 'Aucune note interne.' }}</p>
                </div>
            </div>
        </x-ui.card>
    </div>
</x-dynamic-component>
