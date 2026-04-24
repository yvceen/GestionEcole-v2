<x-teacher-layout title="Conversation">
    <div class="max-w-5xl mx-auto space-y-6">
        <div class="flex items-center justify-between gap-3">
            <a href="{{ route('teacher.messages.index') }}"
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
                <p class="text-sm text-slate-500">Conversation suivie avec statuts de moderation visibles dans le fil.</p>
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
                    <p class="mt-1 text-sm text-slate-500">
                        @if($replyRecipient->role === 'parent')
                            Votre reponse sera visible apres validation admin.
                        @else
                            La reponse sera envoyee directement au destinataire.
                        @endif
                    </p>
                </div>

                <div class="pt-5">
                    @include('partials.message-reply-form', [
                        'replyAction' => route('teacher.messages.store'),
                        'replyRecipient' => $replyRecipient,
                        'replyToId' => $message->id,
                        'replySubject' => $message->subjectText(),
                        'replyLabel' => 'Envoyer la reponse',
                    ])
                </div>
            </section>
        @endif
    </div>
</x-teacher-layout>
