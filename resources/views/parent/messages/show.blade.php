<x-parent-layout title="Conversation">
    <div class="max-w-5xl mx-auto space-y-6">
        <div class="flex items-center justify-between gap-3">
            <a href="{{ route('parent.messages.index') }}"
               class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                Retour a la messagerie
            </a>
            <x-ui.badge :variant="match($message->status ?? 'approved') {
                'pending' => 'warning',
                'approved' => 'success',
                'rejected' => 'danger',
                default => 'info',
            }">
                {{ strtoupper($message->status ?? 'approved') }}
            </x-ui.badge>
        </div>

        <section class="app-card p-5">
            <div class="flex flex-col gap-2 border-b border-slate-200 pb-4">
                <h1 class="text-2xl font-semibold text-slate-900">{{ $message->subjectText() }}</h1>
                <p class="text-sm text-slate-500">Le fil montre vos messages, ceux deja approuves et les statuts de moderation.</p>
            </div>

            <div class="pt-5">
                @include('partials.message-thread', [
                    'threadMessages' => $threadMessages,
                    'currentUserId' => auth()->id(),
                ])
            </div>
        </section>

        @if($replyRecipient)
            <section class="app-card p-5">
                <div class="border-b border-slate-200 pb-4">
                    <h2 class="text-lg font-semibold text-slate-900">Repondre</h2>
                    <p class="mt-1 text-sm text-slate-500">La reponse est ajoutee a la conversation et envoyee directement au destinataire.</p>
                </div>

                <div class="pt-5">
                    @include('partials.message-reply-form', [
                        'replyAction' => route('parent.messages.store'),
                        'replyRecipient' => $replyRecipient,
                        'replyToId' => $message->id,
                        'replySubject' => $message->subjectText(),
                        'replyLabel' => 'Envoyer la reponse',
                    ])
                </div>
            </section>
        @endif
    </div>
</x-parent-layout>
