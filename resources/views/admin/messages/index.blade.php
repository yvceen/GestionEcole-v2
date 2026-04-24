<x-admin-layout title="Messagerie">
    @php
        $mid = (int) request('mid', 0);
        $selected = $mid > 0 ? $messages->getCollection()->firstWhere('thread_id', $mid) : null;
        $isSent = (($folder ?? 'inbox') === 'sent');
    @endphp

    <x-ui.page-header
        title="Messagerie"
        subtitle="Conversations regroupees par fil, avec compteurs de non lus et acces direct a la moderation."
    >
        <x-slot name="actions">
            <form method="GET" class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:items-center">
                @if($isSent)
                    <input type="hidden" name="folder" value="sent">
                @endif

                <div class="relative min-w-[18rem]">
                    <input
                        name="q"
                        value="{{ request('q') }}"
                        class="app-input pr-12"
                        placeholder="Rechercher dans les conversations..."
                    >
                    <button
                        type="submit"
                        class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50"
                    >
                        OK
                    </button>
                </div>
            </form>

            <x-ui.button :href="route('admin.messages.create')" variant="primary">
                Nouveau message
            </x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <div class="grid gap-6 xl:grid-cols-[250px_380px_minmax(0,1fr)]">
        <aside class="app-card p-4">
            <div class="space-y-2">
                <a
                    href="{{ route('admin.messages.index') }}"
                    class="flex items-center justify-between rounded-2xl border px-4 py-3 text-sm font-semibold transition {{ !$isSent ? 'border-sky-200 bg-sky-50 text-sky-800' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}"
                >
                    <span>Boite de reception</span>
                    <span class="text-xs">{{ $counts['inbox'] ?? 0 }}</span>
                </a>

                <a
                    href="{{ route('admin.messages.pending') }}"
                    class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                >
                    <span>En attente</span>
                    <span class="text-xs">{{ $counts['pending'] ?? 0 }}</span>
                </a>

                <a
                    href="{{ route('admin.messages.index', ['folder' => 'sent']) }}"
                    class="flex items-center justify-between rounded-2xl border px-4 py-3 text-sm font-semibold transition {{ $isSent ? 'border-sky-200 bg-sky-50 text-sky-800' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}"
                >
                    <span>Messages envoyes</span>
                    <span class="text-xs">{{ $counts['sent'] ?? 0 }}</span>
                </a>
            </div>

            <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                Les messages enseignants vers les parents gardent leur validation admin actuelle.
            </div>
        </aside>

        <section class="app-card overflow-hidden p-0">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <div>
                    <p class="text-sm font-semibold text-slate-900">{{ $isSent ? 'Conversations suivies' : 'Boite de reception' }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $messages->total() }} fil(s)</p>
                </div>
            </div>

            <div class="max-h-[70vh] overflow-auto">
                @forelse($messages as $thread)
                    @php
                        $threadId = (int) data_get($thread, 'thread_id');
                        $active = $threadId === $mid;
                        $unreadCount = (int) data_get($thread, 'unread_count', 0);
                        $statusVariant = match(data_get($thread, 'status', 'approved')) {
                            'pending' => 'warning',
                            'approved' => 'success',
                            'rejected' => 'danger',
                            default => 'info',
                        };
                    @endphp

                    <a
                        href="{{ route('admin.messages.index', array_filter(['folder' => $isSent ? 'sent' : null, 'mid' => $threadId, 'q' => request('q')])) }}"
                        class="block border-l-4 border-b border-slate-200 px-5 py-4 transition {{ $active ? 'border-l-sky-500 bg-sky-50' : ($unreadCount > 0 ? 'border-l-sky-300 bg-sky-50/60 hover:bg-sky-50' : 'border-l-transparent hover:bg-slate-50') }}"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ data_get($thread, 'participant_label') }}</p>
                                    <x-ui.badge :variant="$statusVariant">{{ strtoupper((string) data_get($thread, 'status', 'approved')) }}</x-ui.badge>
                                    @if($unreadCount > 0)
                                        <span class="inline-flex min-w-6 items-center justify-center rounded-full bg-sky-700 px-2 py-0.5 text-[11px] font-semibold text-white">
                                            {{ $unreadCount }}
                                        </span>
                                    @endif
                                </div>

                                <p class="mt-2 truncate text-sm text-slate-800">{{ data_get($thread, 'subject', '(Sans sujet)') }}</p>
                                <p class="mt-1 truncate text-xs text-slate-500">{{ data_get($thread, 'snippet') }}</p>
                            </div>

                            <div class="shrink-0 text-right">
                                <p class="text-xs text-slate-500">{{ optional(data_get($thread, 'created_at'))->format('d/m H:i') }}</p>
                                @if($unreadCount > 0 && !$isSent)
                                    <p class="mt-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-sky-700">Non lu</p>
                                @endif
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="px-5 py-8 text-sm text-slate-500">Aucune conversation trouvee.</div>
                @endforelse
            </div>

            <div class="border-t border-slate-200 px-5 py-4">
                {{ $messages->links() }}
            </div>
        </section>

        <section class="app-card p-5">
            @if($selected)
                @php
                    $selectedMessage = data_get($selected, 'message');
                    $statusVariant = match(data_get($selected, 'status', 'approved')) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'info',
                    };
                @endphp

                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="truncate text-xl font-semibold text-slate-900">{{ data_get($selected, 'subject', '(Sans sujet)') }}</h2>
                            <x-ui.badge :variant="$statusVariant">{{ strtoupper((string) data_get($selected, 'status', 'approved')) }}</x-ui.badge>
                            @if((int) data_get($selected, 'unread_count', 0) > 0 && !$isSent)
                                <span class="inline-flex items-center rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold text-sky-800">
                                    {{ (int) data_get($selected, 'unread_count', 0) }} nouveau(x)
                                </span>
                            @endif
                        </div>

                        <p class="mt-2 text-sm text-slate-600">
                            {{ $isSent ? 'Participants :' : 'Dernier expediteur :' }}
                            <span class="font-semibold text-slate-900">{{ data_get($selected, 'participant_label') }}</span>
                        </p>
                        <p class="mt-1 text-xs text-slate-500">{{ optional(data_get($selected, 'created_at'))->format('d/m/Y H:i') }}</p>
                    </div>

                    <x-ui.button :href="route('admin.messages.show', $selectedMessage?->thread_key ?? data_get($selected, 'thread_id'))" variant="secondary" size="sm">
                        Ouvrir
                    </x-ui.button>
                </div>

                <div class="my-5 h-px bg-slate-200"></div>

                <div class="rounded-3xl border border-slate-200 bg-slate-50 px-5 py-4 text-sm leading-7 text-slate-700">
                    {!! nl2br(e($selectedMessage?->bodyText() ?? '')) !!}
                </div>
            @else
                <div class="grid h-full min-h-[22rem] place-items-center text-center">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Aucune conversation selectionnee</h2>
                        <p class="mt-2 text-sm text-slate-500">Choisissez un fil dans la liste pour afficher son apercu ici.</p>
                    </div>
                </div>
            @endif
        </section>
    </div>
</x-admin-layout>
