<x-dynamic-component :component="$layoutComponent" title="Suivi de demande" subtitle="Conversation, statut et historique de traitement.">
    @php
        $statusVariant = match($feedbackCase->status) {'resolved', 'closed' => 'success', 'waiting_submitter' => 'warning', 'reviewing' => 'info', default => 'warning'};
    @endphp
    <section class="rounded-[28px] border border-sky-100 bg-gradient-to-r from-sky-50 via-white to-emerald-50 p-6 shadow-sm">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-sky-700">{{ $feedbackCase->reference }}</p>
                <h1 class="mt-2 text-2xl font-bold text-slate-950">{{ $feedbackCase->subject }}</h1>
                <p class="mt-2 text-sm text-slate-600">{{ \App\Models\FeedbackCase::kinds()[$feedbackCase->kind] ?? $feedbackCase->kind }} · {{ \App\Models\FeedbackCase::categories()[$feedbackCase->category] ?? $feedbackCase->category }}</p>
            </div>
            <div class="flex flex-wrap gap-2"><x-ui.badge :variant="$statusVariant">{{ \App\Models\FeedbackCase::statuses()[$feedbackCase->status] ?? $feedbackCase->status }}</x-ui.badge>@if($feedbackCase->is_confidential)<x-ui.badge variant="info">Confidentiel</x-ui.badge>@endif</div>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_minmax(320px,.65fr)]">
        <section class="space-y-4">
            <x-ui.card title="Description initiale" subtitle="Demande envoyée le {{ $feedbackCase->created_at->format('d/m/Y à H:i') }}.">
                <p class="whitespace-pre-line text-sm leading-7 text-slate-700">{{ $feedbackCase->description }}</p>
            </x-ui.card>
            <x-ui.card title="Conversation" subtitle="Toutes les réponses liées à cette demande.">
                <div class="space-y-4">
                    @forelse($feedbackCase->messages as $message)
                        @continue(!$canManage && $message->is_internal)
                        <div class="rounded-2xl border {{ $message->is_internal ? 'border-amber-200 bg-amber-50' : 'border-slate-200 bg-slate-50' }} p-4">
                            <div class="flex flex-wrap items-center justify-between gap-2"><p class="font-bold text-slate-900">{{ $message->user?->name ?? 'Compte supprimé' }}</p><p class="text-xs text-slate-500">{{ $message->created_at->format('d/m/Y H:i') }} @if($message->is_internal) · Note interne @endif</p></div>
                            <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $message->message }}</p>
                        </div>
                    @empty
                        <p class="rounded-2xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">Aucune réponse pour le moment.</p>
                    @endforelse
                </div>
                @if($feedbackCase->status !== 'closed')
                    <form method="POST" action="{{ route($routePrefix.'.reply', $feedbackCase) }}" class="mt-5 space-y-3">
                        @csrf
                        <textarea name="message" rows="4" class="app-input" required placeholder="Écrire une réponse..."></textarea>
                        @if($canManage)<label class="flex items-center gap-2 text-sm font-semibold text-slate-600"><input type="checkbox" name="is_internal" value="1" class="rounded border-slate-300"> Note interne</label>@endif
                        <x-ui.button type="submit" variant="primary">Envoyer la réponse</x-ui.button>
                    </form>
                @endif
            </x-ui.card>
        </section>

        <aside class="space-y-6">
            <x-ui.card title="Informations" subtitle="Contexte de la demande.">
                <dl class="space-y-3 text-sm">
                    <div><dt class="font-bold text-slate-400">Demandeur</dt><dd class="mt-1 text-slate-800">{{ $feedbackCase->submitter?->name ?? 'Compte supprimé' }}</dd></div>
                    <div><dt class="font-bold text-slate-400">Élève</dt><dd class="mt-1 text-slate-800">{{ $feedbackCase->student?->full_name ?? 'Non lié' }}</dd></div>
                    <div><dt class="font-bold text-slate-400">Assignée à</dt><dd class="mt-1 text-slate-800">{{ $feedbackCase->assignedTo?->name ?? 'Non assignée' }}</dd></div>
                    <div><dt class="font-bold text-slate-400">Priorité</dt><dd class="mt-1 text-slate-800">{{ \App\Models\FeedbackCase::priorities()[$feedbackCase->priority] ?? $feedbackCase->priority }}</dd></div>
                </dl>
            </x-ui.card>
            @if($canManage)
                <x-ui.card title="Traitement" subtitle="Statut, priorité et responsable.">
                    <form method="POST" action="{{ route($routePrefix.'.update', $feedbackCase) }}" class="space-y-4">
                        @csrf @method('PUT')
                        <label class="grid gap-2 text-sm font-semibold text-slate-700">Statut<select name="status" class="app-input">@foreach(\App\Models\FeedbackCase::statuses() as $value => $label)<option value="{{ $value }}" @selected($feedbackCase->status === $value)>{{ $label }}</option>@endforeach</select></label>
                        <label class="grid gap-2 text-sm font-semibold text-slate-700">Priorité<select name="priority" class="app-input">@foreach(\App\Models\FeedbackCase::priorities() as $value => $label)<option value="{{ $value }}" @selected($feedbackCase->priority === $value)>{{ $label }}</option>@endforeach</select></label>
                        <label class="grid gap-2 text-sm font-semibold text-slate-700">Responsable<select name="assigned_to_user_id" class="app-input"><option value="">Non assignée</option>@foreach($managers as $manager)<option value="{{ $manager->id }}" @selected($feedbackCase->assigned_to_user_id === $manager->id)>{{ $manager->name }}</option>@endforeach</select></label>
                        <x-ui.button type="submit" variant="primary">Mettre à jour</x-ui.button>
                    </form>
                </x-ui.card>
            @endif
        </aside>
    </div>
</x-dynamic-component>
