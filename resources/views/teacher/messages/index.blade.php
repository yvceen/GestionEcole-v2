<x-teacher-layout title="Messagerie">
    @php
        $mid = (int) request('mid', 0);
        $selected = $mid > 0 ? $messages->getCollection()->firstWhere('thread_id', $mid) : null;
    @endphp

    <div class="mx-auto max-w-6xl space-y-6">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">Messagerie</h1>
                <p class="mt-1 text-sm text-slate-600">Conversations suivies par fil avec etat de lecture et moderation.</p>
            </div>
            <a
                href="{{ route('teacher.messages.create') }}"
                class="rounded-2xl bg-slate-900 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-black"
            >
                Nouveau message
            </a>
        </div>

        <div class="grid gap-6 lg:grid-cols-[380px_1fr]">
            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 bg-slate-50 px-4 py-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">Conversations</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $messages->total() }} fil(s)</p>
                        </div>
                        <p class="text-xs text-slate-500">Les fils non lus restent visibles en tete.</p>
                    </div>
                </div>

                <div class="max-h-[70vh] divide-y divide-slate-200 overflow-auto">
                    @forelse($messages as $thread)
                        @php
                            $threadId = (int) data_get($thread, 'thread_id');
                            $message = data_get($thread, 'message');
                            $isActive = $threadId === $mid;
                            $unreadCount = (int) data_get($thread, 'unread_count', 0);
                            $statusVariant = match(data_get($thread, 'status', 'approved')) {
                                'pending' => 'warning',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                default => 'info',
                            };
                        @endphp

                        <a
                            href="{{ route('teacher.messages.index', ['mid' => $threadId]) }}"
                            class="block border-l-4 px-4 py-4 transition {{ $isActive ? 'border-sky-500 bg-sky-50' : ($unreadCount > 0 ? 'border-sky-300 bg-sky-50/60 hover:bg-sky-50' : 'border-transparent hover:bg-slate-50') }}"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="truncate text-sm font-semibold text-slate-900">
                                            {{ data_get($thread, 'participant_label') }}
                                        </p>
                                        <x-ui.badge :variant="$statusVariant">{{ strtoupper((string) data_get($thread, 'status', 'approved')) }}</x-ui.badge>
                                        @if($unreadCount > 0)
                                            <span class="inline-flex min-w-6 items-center justify-center rounded-full bg-sky-700 px-2 py-0.5 text-[11px] font-semibold text-white">
                                                {{ $unreadCount }}
                                            </span>
                                        @endif
                                    </div>
                                    <p class="mt-2 truncate text-sm font-medium text-slate-800">{{ data_get($thread, 'subject', '(Sans sujet)') }}</p>
                                    <p class="mt-1 truncate text-xs text-slate-500">{{ data_get($thread, 'snippet') }}</p>
                                </div>
                                <div class="shrink-0 text-right">
                                    <p class="text-[11px] text-slate-500">{{ optional(data_get($thread, 'created_at'))->format('d/m H:i') }}</p>
                                    @if($unreadCount > 0)
                                        <p class="mt-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-sky-700">Non lu</p>
                                    @endif
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="p-6 text-center text-sm text-slate-500">Aucun message pour le moment.</div>
                    @endforelse
                </div>

                <div class="border-t border-slate-200 px-4 py-4">
                    {{ $messages->links() }}
                </div>
            </section>

            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                @if($selected)
                    @php
                        $selectedMessage = data_get($selected, 'message');
                    @endphp

                    <div class="border-b border-slate-200 bg-slate-50 px-5 py-4">
                        <div class="flex flex-wrap items-center gap-3">
                            <h2 class="text-lg font-semibold text-slate-900">{{ data_get($selected, 'subject', '(Sans sujet)') }}</h2>
                            @if((int) data_get($selected, 'unread_count', 0) > 0)
                                <span class="inline-flex items-center rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold text-sky-800">
                                    {{ (int) data_get($selected, 'unread_count', 0) }} nouveau(x)
                                </span>
                            @endif
                        </div>
                        <p class="mt-1 text-sm text-slate-500">Le fil est marque comme lu des son ouverture.</p>
                    </div>
                    <div class="p-5">
                        <div class="rounded-3xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm leading-6 text-slate-700">
                            {!! nl2br(e($selectedMessage?->bodyText() ?? '')) !!}
                        </div>
                        <div class="mt-4 flex items-center justify-between gap-3 text-xs text-slate-500">
                            <span>{{ $selectedMessage?->sender?->name ?? 'Utilisateur' }}</span>
                            <span>{{ optional($selectedMessage?->created_at)->format('d/m/Y H:i') }}</span>
                        </div>
                        <div class="mt-4">
                            <x-ui.button :href="route('teacher.messages.show', $selectedMessage?->thread_key ?? data_get($selected, 'thread_id'))" variant="secondary">
                                Ouvrir la conversation
                            </x-ui.button>
                        </div>
                    </div>
                @else
                    <div class="grid min-h-[24rem] place-items-center p-6 text-center text-slate-500">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Aucune conversation selectionnee</h2>
                            <p class="mt-2 text-sm">Choisissez un fil pour afficher son apercu et remettre le compteur a zero.</p>
                        </div>
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-teacher-layout>
