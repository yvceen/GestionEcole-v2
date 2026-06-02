@php
    $routePrefix = $routePrefix ?? 'admin.messages';
    $layoutComponent = $layoutComponent ?? 'admin-layout';
    $canModerate = $canModerate ?? false;
    $canCompose = $canCompose ?? true;
    $replyHelpText = $replyHelpText ?? "La reponse est ajoutee a cette conversation.";

    $status = (string) ($message->status ?? 'approved');
    $statusVariant = match($status) {
        'pending' => 'warning',
        'approved' => 'success',
        'rejected' => 'danger',
        default => 'info',
    };
@endphp

<x-dynamic-component :component="$layoutComponent" title="Conversation">
    <div class="space-y-6">
        @if(session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-900">
                {{ session('success') }}
            </div>
        @endif

        <section class="overflow-hidden rounded-[32px] border border-sky-100 bg-[radial-gradient(circle_at_top_right,_rgba(14,165,233,0.18),_transparent_34%),radial-gradient(circle_at_bottom_left,_rgba(16,185,129,0.14),_transparent_32%),linear-gradient(135deg,#ffffff,#f8fbff_55%,#eefdf8)] px-6 py-6 text-slate-950 shadow-xl shadow-slate-200/70 md:px-8">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="max-w-3xl">
                    <div class="inline-flex items-center gap-2 rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-sky-700">
                        Conversation
                    </div>
                    <h1 class="mt-4 text-3xl font-semibold tracking-tight md:text-4xl">Conversation</h1>
                    <p class="mt-3 text-sm leading-6 text-slate-600">
                        Lisez les messages du fil et repondez directement.
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <x-ui.button :href="route($routePrefix . '.index')" variant="secondary">
                        Retour
                    </x-ui.button>
                    @if($canModerate && $status === 'pending')
                        <form method="POST" action="{{ route($routePrefix . '.approve', $message) }}">
                            @csrf
                            <x-ui.button type="submit" variant="primary">Approuver</x-ui.button>
                        </form>
                    @endif
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-6 py-5">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-xl font-semibold tracking-tight text-slate-950">Fil de discussion</h2>
                        <x-ui.badge :variant="$statusVariant">{{ strtoupper($status) }}</x-ui.badge>
                    </div>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $message->created_at?->format('d/m/Y H:i') ?? '-' }}
                    </p>
                </div>
            </div>

            <div class="bg-gradient-to-br from-slate-50 via-white to-sky-50/40 px-6 py-6">
                @include('partials.message-thread', [
                    'threadMessages' => $threadMessages,
                    'currentUserId' => auth()->id(),
                ])
            </div>
        </section>

        @if($canModerate && $status === 'pending')
            <section class="grid gap-4 lg:grid-cols-2">
                <x-ui.card title="Approuver" subtitle="Le message deviendra visible aux destinataires de la conversation.">
                    <form method="POST" action="{{ route($routePrefix . '.approve', $message) }}" class="space-y-3">
                        @csrf
                        <x-ui.button type="submit" variant="primary">Approuver ce message</x-ui.button>
                    </form>
                </x-ui.card>

                <x-ui.card title="Refuser" subtitle="Ajoutez un motif clair pour expliquer le refus.">
                    <form method="POST" action="{{ route($routePrefix . '.reject', $message) }}" class="space-y-3">
                        @csrf
                        <x-ui.textarea name="reason" label="Motif" rows="4" placeholder="Expliquez la raison du refus...">{{ old('reason', $message->rejection_reason) }}</x-ui.textarea>
                        <x-ui.button type="submit" variant="danger">Refuser ce message</x-ui.button>
                    </form>
                </x-ui.card>
            </section>
        @endif

        @if($canCompose && $replyRecipient)
            <section class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-6 py-5">
                    <h2 class="text-xl font-semibold tracking-tight text-slate-950">Repondre</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $replyHelpText }}</p>
                </div>

                <div class="px-6 py-6">
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
