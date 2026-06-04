<x-dynamic-component :component="$layoutComponent" :title="$visitor->visitor_name" subtitle="Détail de la visite, destination et suivi des horaires.">
    @if(session('success'))<x-ui.alert variant="success">{{ session('success') }}</x-ui.alert>@endif
    @php
        $variant = match($visitor->status) {'checked_in' => 'success', 'checked_out' => 'info', 'cancelled' => 'danger', default => 'warning'};
        $label = match($visitor->status) {'checked_in' => 'Présent dans l’établissement', 'checked_out' => 'Visite terminée', 'cancelled' => 'Visite annulée', default => 'Visite prévue'};
    @endphp
    <section class="rounded-[28px] border border-sky-100 bg-gradient-to-r from-sky-50 via-white to-emerald-50 p-6 shadow-sm">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div><div class="flex flex-wrap items-center gap-3"><h1 class="text-2xl font-bold text-slate-950">{{ $visitor->visitor_name }}</h1><x-ui.badge :variant="$variant">{{ $label }}</x-ui.badge></div><p class="mt-2 text-sm text-slate-600">{{ $visitor->phone ?: 'Téléphone non renseigné' }} · Badge <strong>{{ $visitor->badge_code }}</strong></p></div>
            <x-ui.button :href="route($routePrefix.'.index')" variant="secondary">Retour au registre</x-ui.button>
        </div>
        <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-white bg-white/80 p-4"><p class="text-xs uppercase tracking-wide text-slate-500">Arrivée prévue</p><p class="mt-2 font-bold text-slate-950">{{ $visitor->expected_at?->format('d/m/Y H:i') ?? '-' }}</p></div>
            <div class="rounded-2xl border border-white bg-white/80 p-4"><p class="text-xs uppercase tracking-wide text-slate-500">Entrée réelle</p><p class="mt-2 font-bold text-slate-950">{{ $visitor->checked_in_at?->format('d/m/Y H:i') ?? '-' }}</p></div>
            <div class="rounded-2xl border border-white bg-white/80 p-4"><p class="text-xs uppercase tracking-wide text-slate-500">Sortie réelle</p><p class="mt-2 font-bold text-slate-950">{{ $visitor->checked_out_at?->format('d/m/Y H:i') ?? '-' }}</p></div>
            <div class="rounded-2xl border border-white bg-white/80 p-4"><p class="text-xs uppercase tracking-wide text-slate-500">Durée</p><p class="mt-2 font-bold text-slate-950">{{ $visitor->checked_in_at && $visitor->checked_out_at ? $visitor->checked_in_at->diffForHumans($visitor->checked_out_at, true) : '-' }}</p></div>
        </div>
    </section>

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1.35fr)_minmax(320px,.65fr)]">
        <x-ui.card title="Informations de visite" subtitle="Identité, motif et destination.">
            <dl class="grid gap-4 sm:grid-cols-2">
                @foreach([
                    'Motif' => \App\Models\VisitorVisit::purposes()[$visitor->purpose] ?? $visitor->purpose,
                    'Personne visitée' => $visitor->hostUser?->name ?? 'Accueil général',
                    'Élève concerné' => $visitor->student?->full_name ?? '-',
                    'Classe' => $visitor->student?->classroom?->name ?? '-',
                    'Pièce d’identité' => trim(($visitor->identity_type ?: '').' '.($visitor->identity_number ?: '')) ?: '-',
                    'Organisation' => $visitor->organization ?: '-',
                    'Véhicule' => $visitor->vehicle_plate ?: '-',
                    'Enregistré par' => $visitor->createdBy?->name ?? '-',
                ] as $term => $value)
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ $term }}</dt><dd class="mt-2 font-semibold text-slate-900">{{ $value }}</dd></div>
                @endforeach
            </dl>
            @if($visitor->purpose_details)<div class="mt-4 rounded-2xl border border-sky-100 bg-sky-50 p-4 text-sm leading-6 text-slate-700">{{ $visitor->purpose_details }}</div>@endif
        </x-ui.card>

        <aside class="space-y-5">
            @if($visitor->status === 'expected')
                <x-ui.card title="Confirmer l’arrivée" subtitle="Enregistrez l’entrée réelle du visiteur.">
                    <form method="POST" action="{{ route($routePrefix.'.check-in', $visitor) }}" class="space-y-3">@csrf @method('PUT')<x-ui.textarea name="entry_note" label="Note d’entrée" rows="3">{{ $visitor->entry_note }}</x-ui.textarea><x-ui.button type="submit" variant="primary">Confirmer l’entrée</x-ui.button></form>
                    <form method="POST" action="{{ route($routePrefix.'.cancel', $visitor) }}" class="mt-3">@csrf @method('PUT')<x-ui.button type="submit" variant="secondary">Annuler la visite</x-ui.button></form>
                </x-ui.card>
            @elseif($visitor->status === 'checked_in')
                <x-ui.card title="Enregistrer la sortie" subtitle="Terminez la visite lorsque la personne quitte l’établissement.">
                    <form method="POST" action="{{ route($routePrefix.'.check-out', $visitor) }}" class="space-y-3">@csrf @method('PUT')<x-ui.textarea name="exit_note" label="Note de sortie" rows="3"></x-ui.textarea><x-ui.button type="submit" variant="primary">Confirmer la sortie</x-ui.button></form>
                </x-ui.card>
            @endif
            @if($visitor->entry_note || $visitor->exit_note)<div class="rounded-[24px] border border-amber-100 bg-amber-50 p-5 text-sm text-slate-700">@if($visitor->entry_note)<p><strong>Entrée :</strong> {{ $visitor->entry_note }}</p>@endif @if($visitor->exit_note)<p class="mt-2"><strong>Sortie :</strong> {{ $visitor->exit_note }}</p>@endif</div>@endif
        </aside>
    </div>
</x-dynamic-component>
