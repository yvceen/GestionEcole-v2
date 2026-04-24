<x-director-layout title="Messagerie">
    @php
        $isSent = (($folder ?? 'inbox') === 'sent');
    @endphp

    <div class="max-w-5xl mx-auto">
        @if(session('success'))
            <div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                {{ session('success') }}
            </div>
        @endif

        <div class="flex flex-col md:flex-row gap-6">
            {{-- Sidebar --}}
            <aside class="md:w-64 rounded-2xl border border-slate-200 bg-white p-4 h-fit">
                <div class="space-y-2">
                    <a href="{{ route('director.messages.index') }}"
                       class="flex items-center justify-between rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold hover:bg-slate-50 transition {{ !$isSent ? 'bg-slate-50 border-slate-900/20' : 'bg-white' }}">
                        <span>Boîte de réception</span>
                        <span class="text-xs text-slate-600">{{ $counts['inbox'] ?? 0 }}</span>
                    </a>

                    <a href="{{ route('director.messages.index', ['folder' => 'sent']) }}"
                       class="flex items-center justify-between rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold hover:bg-slate-50 transition {{ $isSent ? 'bg-slate-50 border-slate-900/20' : 'bg-white' }}">
                        <span>Envoyés</span>
                        <span class="text-xs text-slate-600">{{ $counts['sent'] ?? 0 }}</span>
                    </a>
                </div>

                <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4 text-xs text-slate-600">
                    Consultez vos messages reçus des enseignants, parents et administrateurs.
                </div>
            </aside>

            {{-- Messages List --}}
            <section class="flex-1 rounded-2xl border border-slate-200 bg-white overflow-hidden">
                <div class="p-4 border-b border-slate-200 flex items-center justify-between gap-3">
                    <div>
                        <div class="text-sm font-semibold text-slate-900">
                            {{ $isSent ? 'Messages envoyés' : 'Boîte de réception' }}
                        </div>
                        <div class="text-xs text-slate-500">{{ count($messages ?? []) }} message(s)</div>
                    </div>

                    <form method="GET" class="flex items-center gap-2">
                        @if($isSent)
                            <input type="hidden" name="folder" value="sent">
                        @endif
                        <input name="q" value="{{ $q ?? '' }}"
                               class="w-40 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-slate-900/10"
                               placeholder="Rechercher...">
                        <button class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold hover:bg-slate-50 transition">
                            OK
                        </button>
                    </form>
                </div>

                <div class="max-h-[60vh] overflow-auto divide-y divide-slate-200">
                    @forelse(($messages ?? collect()) as $m)
                        <a href="{{ route('director.messages.show', $m) }}"
                           class="block p-4 hover:bg-slate-50 transition border-l-4 border-transparent hover:border-slate-900">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="font-semibold text-slate-900 truncate">
                                        {{ $m->subject ?: '(Sans sujet)' }}
                                    </div>
                                    <div class="text-sm text-slate-600 truncate">
                                        {{ $m->sender?->name ?? 'Utilisateur inconnu' }}
                                    </div>
                                    <div class="mt-1 text-xs text-slate-500 line-clamp-2">
                                        {{ strip_tags($m->body) }}
                                    </div>
                                </div>
                                <div class="text-xs text-slate-500 whitespace-nowrap">
                                    {{ $m->created_at?->format('d/m/Y H:i') ?? '—' }}
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="p-8 text-center text-slate-500">
                            Aucun message pour le moment.
                        </div>
                    @endforelse
                </div>

                @if($messages?->hasPages())
                    <div class="p-4 border-t border-slate-200">
                        {{ $messages->links() }}
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-director-layout>
