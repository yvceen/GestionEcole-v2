@php
    $routePrefix = $routePrefix ?? 'admin.messages';
    $layoutComponent = $layoutComponent ?? 'admin-layout';
    $canModerate = $canModerate ?? true;
    $canCompose = $canCompose ?? true;
@endphp

<x-dynamic-component :component="$layoutComponent" title="Conversation">
    <div class="space-y-6">
        <x-ui.page-header
            title="Conversation"
            subtitle="Visualisez le fil complet, validez les messages en attente et repondez depuis la meme page."
        >
            <x-slot name="actions">
                <x-ui.button :href="route($routePrefix . '.index')" variant="secondary">
                    Retour
                </x-ui.button>

                @if($canModerate && ($message->status ?? null) === 'pending')
                    <form method="POST" action="{{ route($routePrefix . '.approve', $message) }}">
                        @csrf
                        <x-ui.button type="submit" variant="outline">Approuver</x-ui.button>
                    </form>
                @endif
            </x-slot>
        </x-ui.page-header>

        <section class="app-card p-5">
            <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 pb-4">
                <h2 class="text-xl font-semibold text-slate-900">{{ $message->subjectText() }}</h2>
                <x-ui.badge :variant="match($message->status ?? 'approved') {
                    'pending' => 'warning',
                    'approved' => 'success',
                    'rejected' => 'danger',
                    default => 'info',
                }">
                    {{ strtoupper($message->status ?? 'approved') }}
                </x-ui.badge>
            </div>

            <div class="pt-5">
                @include('partials.message-thread', [
                    'threadMessages' => $threadMessages,
                    'currentUserId' => auth()->id(),
                ])
            </div>
        </section>

        @if($canModerate && ($message->status ?? null) === 'pending')
            <section class="grid gap-4 lg:grid-cols-2">
                <x-ui.card title="Approuver" subtitle="Le message deviendra visible aux destinataires de la conversation.">
                    <form method="POST" action="{{ route($routePrefix . '.approve', $message) }}" class="space-y-3">
                        @csrf
                        <x-ui.button type="submit" variant="primary">Approuver ce message</x-ui.button>
                    </form>
                </x-ui.card>

                <x-ui.card title="Refuser" subtitle="Ajoutez un motif clair pour que l'expediteur comprenne le refus.">
                    <form method="POST" action="{{ route($routePrefix . '.reject', $message) }}" class="space-y-3">
                        @csrf
                        <x-ui.textarea name="reason" label="Motif" rows="4" placeholder="Expliquez la raison du refus...">{{ old('reason', $message->rejection_reason) }}</x-ui.textarea>
                        <x-ui.button type="submit" variant="danger">Refuser ce message</x-ui.button>
                    </form>
                </x-ui.card>
            </section>
        @endif

        @if($canCompose && $replyRecipient)
            <section class="app-card p-5">
                <div class="border-b border-slate-200 pb-4">
                    <h2 class="text-lg font-semibold text-slate-900">Repondre dans le fil</h2>
                    <p class="mt-1 text-sm text-slate-500">La reponse admin est envoyee directement et reste rattachee a cette conversation.</p>
                </div>

                <div class="pt-5">
                    @include('partials.message-reply-form', [
                        'replyAction' => route($routePrefix . '.store'),
                        'replyRecipient' => $replyRecipient,
                        'replyToId' => $message->id,
                        'replySubject' => $message->subjectText(),
                        'replyLabel' => 'Envoyer la reponse',
                    ])
                </div>
            </section>
        @endif
    </div>
</x-dynamic-component>
