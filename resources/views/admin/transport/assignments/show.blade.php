<x-admin-layout title="Détail de l'affectation">
    @php
        $startDate = $transportAssignment->assigned_date;
        $endDate = $transportAssignment->ended_date ?? now();
        $days = $startDate ? $endDate->diffInDays($startDate) : 0;
        $months = $startDate ? $endDate->diffInMonths($startDate) : 0;
    @endphp

    <x-ui.page-header
        :title="$transportAssignment->student?->full_name"
        :subtitle="'Route : '.($transportAssignment->route?->route_name ?? 'Non renseignée')"
    >
        <x-slot name="actions">
            <x-ui.button :href="route('admin.transport.assignments.edit', $transportAssignment)" variant="primary">
                Modifier
            </x-ui.button>
            <x-ui.button :href="route('admin.transport.assignments.index')" variant="secondary">
                Retour
            </x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1.4fr)_320px]">
        <div class="space-y-6">
            <x-ui.card title="Élève">
                @if($transportAssignment->student)
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between gap-4"><span class="text-slate-500">Nom</span><span class="font-semibold text-slate-900">{{ $transportAssignment->student->full_name }}</span></div>
                        <div class="flex justify-between gap-4"><span class="text-slate-500">Classe</span><span class="text-slate-900">{{ $transportAssignment->student->classroom?->name ?? '—' }}</span></div>
                        <div class="flex justify-between gap-4"><span class="text-slate-500">Parent</span><span class="text-slate-900">{{ $transportAssignment->student->parentUser?->name ?? '—' }}</span></div>
                        <div class="flex justify-between gap-4"><span class="text-slate-500">Téléphone</span><span class="text-slate-900">{{ $transportAssignment->student->parentUser?->phone ?? '—' }}</span></div>
                    </div>
                @else
                    <p class="text-sm text-slate-500">Informations non disponibles.</p>
                @endif
            </x-ui.card>

            <x-ui.card title="Transport">
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <span class="text-slate-500">Route</span>
                        <span class="text-slate-900">
                            @if($transportAssignment->route)
                                <a href="{{ route('admin.transport.routes.show', $transportAssignment->route) }}" class="font-semibold text-sky-700 hover:text-sky-800">
                                    {{ $transportAssignment->route->route_name }}
                                </a>
                            @else
                                —
                            @endif
                        </span>
                    </div>
                    <div class="flex justify-between gap-4"><span class="text-slate-500">Point de ramassage</span><span class="font-semibold text-slate-900">{{ $transportAssignment->pickup_point ?? '—' }}</span></div>
                    <div class="flex justify-between gap-4"><span class="text-slate-500">Tarif mensuel</span><span class="font-semibold text-slate-900">{{ number_format($transportAssignment->route?->monthly_fee ?? 0, 2, ',', ' ') }} DH</span></div>
                </div>
            </x-ui.card>

            <x-ui.card title="Période">
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4"><span class="text-slate-500">Date de début</span><span class="font-semibold text-slate-900">{{ optional($transportAssignment->assigned_date)->format('d/m/Y') }}</span></div>
                    <div class="flex justify-between gap-4"><span class="text-slate-500">Date de fin</span><span class="text-slate-900">{{ $transportAssignment->ended_date ? $transportAssignment->ended_date->format('d/m/Y') : 'En cours' }}</span></div>
                    <div class="flex justify-between gap-4"><span class="text-slate-500">Durée</span><span class="font-semibold text-slate-900">{{ $months > 0 ? $months.' mois' : $days.' jours' }}</span></div>
                </div>
            </x-ui.card>
        </div>

        <div class="space-y-6">
            <x-ui.card title="Statut">
                <x-ui.badge :variant="$transportAssignment->is_active && !$transportAssignment->ended_date ? 'success' : 'info'">
                    {{ $transportAssignment->is_active && !$transportAssignment->ended_date ? 'Actif' : 'Terminé' }}
                </x-ui.badge>
            </x-ui.card>

            <x-ui.card title="Véhicule">
                @if($transportAssignment->route?->vehicle)
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-semibold text-slate-900">{{ $transportAssignment->route->vehicle->registration_number }}</p>
                            <p class="text-sm text-slate-500">{{ $transportAssignment->route->vehicle->vehicle_type }}</p>
                        </div>
                        <x-ui.button :href="route('admin.transport.vehicles.show', $transportAssignment->route->vehicle)" variant="ghost" size="sm">
                            Voir
                        </x-ui.button>
                    </div>
                @else
                    <p class="text-sm text-slate-500">Véhicule non disponible.</p>
                @endif
            </x-ui.card>

            <x-ui.card title="Dates système">
                <div class="space-y-3 text-sm">
                    <div><span class="text-slate-500">Créé</span><div class="text-slate-900">{{ optional($transportAssignment->created_at)->format('d/m/Y H:i') }}</div></div>
                    <div><span class="text-slate-500">Modifié</span><div class="text-slate-900">{{ optional($transportAssignment->updated_at)->format('d/m/Y H:i') }}</div></div>
                </div>
            </x-ui.card>
        </div>
    </div>
</x-admin-layout>
