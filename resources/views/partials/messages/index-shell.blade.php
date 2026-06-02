@php
    $routePrefix = $routePrefix ?? 'admin.messages';
    $layoutComponent = $layoutComponent ?? 'admin-layout';
    $canCompose = $canCompose ?? true;
    $canModerate = $canModerate ?? false;
    $supportsFolders = $supportsFolders ?? true;
    $folder = $folder ?? 'inbox';

    $mid = (int) request('mid', 0);
    $selected = $mid > 0 ? $messages->getCollection()->firstWhere('thread_id', $mid) : null;
    $isSent = $supportsFolders && $folder === 'sent';
    $selectedMessage = $selected ? data_get($selected, 'message') : null;

    $initials = static function (?string $name): string {
        $parts = collect(preg_split('/\s+/', trim((string) $name)) ?: [])
            ->filter()
            ->take(2)
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');

        return $parts !== '' ? $parts : 'ME';
    };

    $threadUrl = static function (int $threadId) use ($routePrefix, $isSent, $supportsFolders) {
        return route($routePrefix . '.index', array_filter([
            'folder' => ($supportsFolders && $isSent) ? 'sent' : null,
            'mid' => $threadId,
            'q' => request('q'),
        ]));
    };
@endphp

<x-dynamic-component :component="$layoutComponent" title="Messagerie">
    <section class="overflow-hidden rounded-[32px] border border-slate-200 bg-gradient-to-br from-slate-950 via-slate-900 to-sky-900 px-6 py-6 text-white shadow-xl shadow-slate-200/70 md:px-8">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="max-w-2xl">
                <div class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-sky-100">
                    <span class="h-2 w-2 rounded-full bg-emerald-300"></span>
                    Messagerie
                </div>
                <h1 class="mt-4 text-3xl font-semibold tracking-tight md:text-4xl">Discussions administratives</h1>
                <p class="mt-3 text-sm leading-6 text-slate-200">
                    Suivez les conversations avec les familles et les equipes depuis une interface claire, moderne et rapide.
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                @if($canModerate && Route::has($routePrefix . '.pending'))
                    <x-ui.button :href="route($routePrefix . '.pending')" variant="secondary">
                        Validation
                    </x-ui.button>
                @endif
                @if($canCompose && Route::has($routePrefix . '.create'))
                    <x-ui.button :href="route($routePrefix . '.create')" variant="primary">
                        Nouveau message
                    </x-ui.button>
                @endif
            </div>
        </div>
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-[390px_minmax(0,1fr)]">
        <aside class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-5">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-xl font-semibold tracking-tight text-slate-950">Messages</h2>
                        <p class="mt-1 text-xs font-medium text-slate-500">{{ $messages->total() }} conversation(s)</p>
                    </div>
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-sky-50 text-sky-700 ring-1 ring-sky-100">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8M8 14h5M21 12a8.5 8.5 0 0 1-8.5 8.5H6l-3 2 .8-4.3A8.5 8.5 0 1 1 21 12Z"/>
                        </svg>
                    </span>
                </div>

                <div class="mt-5 grid {{ $supportsFolders ? 'grid-cols-3' : 'grid-cols-1' }} gap-2 text-sm font-semibold">
                    <a href="{{ route($routePrefix . '.index') }}" class="rounded-2xl px-3 py-2 text-center transition {{ !$isSent ? 'bg-sky-600 text-white shadow-sm shadow-sky-200' : 'bg-slate-50 text-slate-600 hover:bg-slate-100' }}">
                        {{ $supportsFolders ? 'Recus' : 'Discussions' }}
                    </a>
                    @if($supportsFolders)
                        @if($canModerate && Route::has($routePrefix . '.pending'))
                            <a href="{{ route($routePrefix . '.pending') }}" class="rounded-2xl bg-slate-50 px-3 py-2 text-center text-slate-600 transition hover:bg-slate-100">
                                Attente
                            </a>
                        @else
                            <span class="rounded-2xl bg-slate-50 px-3 py-2 text-center text-slate-400">Attente</span>
                        @endif
                        <a href="{{ route($routePrefix . '.index', ['folder' => 'sent']) }}" class="rounded-2xl px-3 py-2 text-center transition {{ $isSent ? 'bg-sky-600 text-white shadow-sm shadow-sky-200' : 'bg-slate-50 text-slate-600 hover:bg-slate-100' }}">
                            Envoyes
                        </a>
                    @endif
                </div>

                <form method="GET" class="mt-4">
                    @if($isSent)
                        <input type="hidden" name="folder" value="sent">
                    @endif
                    <div class="relative">
                        <input
                            name="q"
                            value="{{ request('q') }}"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 pr-12 text-sm text-slate-900 placeholder:text-slate-400 focus:border-sky-300 focus:ring-sky-100"
                            placeholder="Rechercher une discussion..."
                        >
                        <button type="submit" class="absolute right-2 top-1/2 inline-flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-xl bg-white text-slate-500 shadow-sm ring-1 ring-slate-200 transition hover:text-sky-700">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.3-4.3M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z"/>
                            </svg>
                        </button>
                    </div>
                </form>
            </div>

            <div class="max-h-[calc(100vh-25rem)] min-h-[28rem] overflow-auto">
                @forelse($messages as $thread)
                    @php
                        $threadId = (int) data_get($thread, 'thread_id');
                        $active = $threadId === $mid;
                        $unreadCount = (int) data_get($thread, 'unread_count', 0);
                        $participant = (string) data_get($thread, 'participant_label', 'Utilisateur');
                        $subject = (string) data_get($thread, 'subject', '(Sans sujet)');
                        $status = (string) data_get($thread, 'status', 'approved');
                        $statusVariant = match($status) {
                            'pending' => 'warning',
                            'approved' => 'success',
                            'rejected' => 'danger',
                            default => 'info',
                        };
                    @endphp

                    <a href="{{ $threadUrl($threadId) }}" class="group block border-b border-slate-100 px-5 py-4 transition {{ $active ? 'bg-sky-50' : 'hover:bg-slate-50' }}">
                        <div class="flex items-start gap-3">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl {{ $active ? 'bg-sky-600 text-white' : 'bg-slate-100 text-slate-700 group-hover:bg-sky-100 group-hover:text-sky-700' }} text-sm font-bold">
                                {{ $initials($participant) }}
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-slate-950">{{ $participant }}</p>
                                        <p class="mt-1 truncate text-sm font-semibold {{ $unreadCount > 0 ? 'text-slate-950' : 'text-slate-600' }}">{{ $subject }}</p>
                                    </div>
                                    <p class="shrink-0 text-xs text-slate-400">{{ optional(data_get($thread, 'created_at'))->format('H:i') }}</p>
                                </div>

                                <p class="mt-1 truncate text-xs leading-5 text-slate-500">{{ data_get($thread, 'snippet') }}</p>

                                <div class="mt-3 flex items-center justify-between gap-2">
                                    <x-ui.badge :variant="$statusVariant">{{ strtoupper($status) }}</x-ui.badge>
                                    @if($unreadCount > 0 && !$isSent)
                                        <span class="inline-flex min-w-6 items-center justify-center rounded-full bg-sky-600 px-2 py-0.5 text-[11px] font-semibold text-white">
                                            {{ $unreadCount }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="px-5 py-12 text-center">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-50 text-slate-400">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8M8 14h4M21 12a8.5 8.5 0 0 1-8.5 8.5H6l-3 2 .8-4.3A8.5 8.5 0 1 1 21 12Z"/>
                            </svg>
                        </div>
                        <p class="mt-4 text-sm font-semibold text-slate-900">Aucune discussion</p>
                        <p class="mt-1 text-sm text-slate-500">Les conversations apparaitront ici.</p>
                    </div>
                @endforelse
            </div>

            <div class="border-t border-slate-100 px-5 py-4">
                {{ $messages->links() }}
            </div>
        </aside>

        <section class="min-h-[42rem] overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm">
            @if($selected)
                @php
                    $status = (string) data_get($selected, 'status', 'approved');
                    $statusVariant = match($status) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'info',
                    };
                    $participant = (string) data_get($selected, 'participant_label', 'Utilisateur');
                    $showTarget = $selectedMessage?->thread_key ?? data_get($selected, 'thread_id');
                @endphp

                <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                    <div class="flex min-w-0 items-start gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-sky-600 text-sm font-bold text-white">
                            {{ $initials($participant) }}
                        </div>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="truncate text-xl font-semibold tracking-tight text-slate-950">{{ data_get($selected, 'subject', '(Sans sujet)') }}</h2>
                                <x-ui.badge :variant="$statusVariant">{{ strtoupper($status) }}</x-ui.badge>
                            </div>
                            <p class="mt-1 text-sm text-slate-500">
                                {{ $isSent ? 'Participants' : 'Conversation avec' }}
                                <span class="font-semibold text-slate-800">{{ $participant }}</span>
                            </p>
                            <p class="mt-1 text-xs text-slate-400">{{ optional(data_get($selected, 'created_at'))->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>

                    <x-ui.button :href="route($routePrefix . '.show', $showTarget)" variant="secondary" size="sm">
                        Ouvrir le fil
                    </x-ui.button>
                </div>

                <div class="flex min-h-[32rem] flex-col justify-between bg-gradient-to-br from-slate-50 via-white to-sky-50/50 px-6 py-6">
                    <div class="max-w-3xl">
                        <div class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-500 ring-1 ring-slate-200">
                            Dernier message
                        </div>
                        <div class="mt-4 rounded-[28px] border border-slate-200 bg-white px-5 py-4 shadow-sm">
                            <p class="text-sm leading-7 text-slate-700">
                                {!! nl2br(e($selectedMessage?->bodyText() ?? '')) !!}
                            </p>
                        </div>
                    </div>

                    <div class="mt-8 flex flex-wrap items-center justify-between gap-3 rounded-[24px] border border-slate-200 bg-white px-5 py-4">
                        <div>
                            <p class="text-sm font-semibold text-slate-950">{{ (int) data_get($selected, 'unread_count', 0) }} nouveau(x) message(s)</p>
                            <p class="mt-1 text-xs text-slate-500">Ouvrez le fil complet pour repondre ou consulter l'historique.</p>
                        </div>
                        <x-ui.button :href="route($routePrefix . '.show', $showTarget)" variant="primary" size="sm">
                            Voir la discussion
                        </x-ui.button>
                    </div>
                </div>
            @else
                <div class="grid h-full min-h-[42rem] place-items-center bg-gradient-to-br from-slate-50 via-white to-sky-50 text-center">
                    <div class="px-6">
                        <div class="mx-auto flex h-24 w-24 items-center justify-center rounded-[32px] bg-amber-50 text-amber-500 shadow-sm ring-1 ring-amber-100">
                            <svg class="h-12 w-12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 13.5h5M7.5 9.5h9M21 11.5a8 8 0 0 1-8 8H7l-4 2 1.2-4A8 8 0 1 1 21 11.5Z"/>
                            </svg>
                        </div>
                        <h2 class="mt-6 text-xl font-semibold tracking-tight text-slate-950">Aucune conversation selectionnee</h2>
                        <p class="mt-2 text-sm text-slate-500">Choisissez une discussion dans la liste pour afficher son apercu.</p>
                    </div>
                </div>
            @endif
        </section>
    </section>
</x-dynamic-component>
