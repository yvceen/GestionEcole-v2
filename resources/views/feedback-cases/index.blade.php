<x-dynamic-component :component="$layoutComponent" title="Réclamations et suggestions" subtitle="Suivi clair des demandes, idées et remarques de la communauté scolaire.">
    <section class="rounded-[28px] border border-sky-100 bg-gradient-to-r from-sky-50 via-white to-emerald-50 p-6 shadow-sm">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-sky-700">Écoute et amélioration</p>
                <h1 class="mt-2 text-2xl font-bold text-slate-950">Réclamations et suggestions</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">{{ $canManage ? 'Classez, traitez et répondez aux retours avec un suivi traçable.' : 'Envoyez une remarque ou une idée et suivez la réponse de l’établissement.' }}</p>
            </div>
            @if($canCreate)
                <x-ui.button :href="route($routePrefix.'.create')" variant="primary">Nouvelle demande</x-ui.button>
            @endif
        </div>
        <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-sky-100 bg-sky-50 p-4"><p class="text-xs font-bold uppercase tracking-wide text-sky-700">Nouvelles</p><p class="mt-2 text-3xl font-bold text-slate-950">{{ $stats['new'] }}</p></div>
            <div class="rounded-2xl border border-amber-100 bg-amber-50 p-4"><p class="text-xs font-bold uppercase tracking-wide text-amber-700">En traitement</p><p class="mt-2 text-3xl font-bold text-slate-950">{{ $stats['reviewing'] }}</p></div>
            <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4"><p class="text-xs font-bold uppercase tracking-wide text-emerald-700">Résolues</p><p class="mt-2 text-3xl font-bold text-slate-950">{{ $stats['resolved'] }}</p></div>
            <div class="rounded-2xl border border-rose-100 bg-rose-50 p-4"><p class="text-xs font-bold uppercase tracking-wide text-rose-700">Réclamations</p><p class="mt-2 text-3xl font-bold text-slate-950">{{ $stats['complaints'] }}</p></div>
        </div>
    </section>

    <x-ui.card title="Recherche et filtres" subtitle="Filtrez par référence, sujet, type ou statut.">
        <form method="GET" class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_190px_190px_auto_auto]">
            <input name="q" value="{{ $q }}" class="app-input" placeholder="Référence, sujet, personne...">
            <select name="kind" class="app-input"><option value="">Tous les types</option>@foreach(\App\Models\FeedbackCase::kinds() as $value => $label)<option value="{{ $value }}" @selected($kind === $value)>{{ $label }}</option>@endforeach</select>
            <select name="status" class="app-input"><option value="">Tous les statuts</option>@foreach(\App\Models\FeedbackCase::statuses() as $value => $label)<option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>@endforeach</select>
            <x-ui.button type="submit" variant="primary">Filtrer</x-ui.button>
            <x-ui.button :href="route($routePrefix.'.index')" variant="secondary">Reset</x-ui.button>
        </form>
    </x-ui.card>

    <section class="grid gap-4">
        @forelse($cases as $case)
            @php
                $statusVariant = match($case->status) {'resolved', 'closed' => 'success', 'waiting_submitter' => 'warning', 'reviewing' => 'info', default => 'warning'};
                $priorityClass = match($case->priority) {'urgent' => 'bg-rose-50 text-rose-700 border-rose-200', 'high' => 'bg-amber-50 text-amber-700 border-amber-200', 'low' => 'bg-slate-50 text-slate-600 border-slate-200', default => 'bg-sky-50 text-sky-700 border-sky-200'};
            @endphp
            <a href="{{ route($routePrefix.'.show', $case) }}" class="grid gap-4 rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm transition hover:border-sky-200 hover:shadow-md lg:grid-cols-[minmax(0,1.35fr)_minmax(0,.9fr)_minmax(0,.8fr)_auto] lg:items-center">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full border px-3 py-1 text-xs font-bold {{ $priorityClass }}">{{ \App\Models\FeedbackCase::priorities()[$case->priority] ?? $case->priority }}</span>
                        <x-ui.badge :variant="$statusVariant">{{ \App\Models\FeedbackCase::statuses()[$case->status] ?? $case->status }}</x-ui.badge>
                        @if($case->is_confidential)<x-ui.badge variant="info">Confidentiel</x-ui.badge>@endif
                    </div>
                    <h2 class="mt-3 text-lg font-bold text-slate-950">{{ $case->subject }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $case->reference }} · {{ \App\Models\FeedbackCase::kinds()[$case->kind] ?? $case->kind }} · {{ \App\Models\FeedbackCase::categories()[$case->category] ?? $case->category }}</p>
                </div>
                <div><p class="text-xs font-bold uppercase tracking-wide text-slate-400">Demandeur</p><p class="mt-1 font-semibold text-slate-800">{{ $case->submitter?->name ?? 'Compte supprimé' }}</p><p class="text-xs text-slate-500">{{ $case->student?->full_name ?: 'Sans élève lié' }}</p></div>
                <div><p class="text-xs font-bold uppercase tracking-wide text-slate-400">Suivi</p><p class="mt-1 font-semibold text-slate-800">{{ $case->assignedTo?->name ?? 'Non assignée' }}</p><p class="text-xs text-slate-500">{{ $case->messages_count }} message(s)</p></div>
                <span class="text-sm font-bold text-sky-700">Ouvrir</span>
            </a>
        @empty
            <div class="rounded-[26px] border border-dashed border-slate-300 bg-white px-6 py-16 text-center text-sm text-slate-500">Aucune demande pour le moment.</div>
        @endforelse
    </section>
    <div>{{ $cases->links() }}</div>
</x-dynamic-component>
