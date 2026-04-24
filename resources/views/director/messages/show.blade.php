<x-director-layout title="Message">
    <div class="max-w-4xl mx-auto">
        @if(session('success'))
            <div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                {{ session('success') }}
            </div>
        @endif

        <div class="flex items-center justify-between mb-6">
            <a href="{{ route('director.messages.index') }}"
               class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                ← Retour à la messagerie
            </a>

            <div class="text-xs text-slate-500">
                {{ $message->created_at?->format('d/m/Y H:i') ?? '—' }}
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6">
            <div class="text-2xl font-semibold text-slate-900 mb-2">
                {{ $message->subject ?: '(Sans sujet)' }}
            </div>

            <div class="text-sm text-slate-600 mb-6 pb-6 border-b border-slate-200">
                <span class="font-semibold">De :</span> {{ $message->sender?->name ?? 'Utilisateur inconnu' }}
                <span class="text-xs text-slate-500 ml-2">({{ $message->sender?->email ?? '—' }})</span>
            </div>

            <div class="prose prose-sm max-w-none text-slate-700">
                {!! nl2br(htmlspecialchars($message->body)) !!}
            </div>

            @if($message->attachments && count($message->attachments) > 0)
                <div class="mt-6 pt-6 border-t border-slate-200">
                    <div class="text-sm font-semibold text-slate-900 mb-3">Pièces jointes</div>
                    <div class="space-y-2">
                        @foreach($message->attachments as $file)
                            <a href=".../download/{{ $file }}"
                               class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm hover:bg-slate-100 transition">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="7 10 12 15 17 10"></polyline>
                                    <line x1="12" y1="15" x2="12" y2="3"></line>
                                </svg>
                                {{ $file }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="mt-6 pt-6 border-t border-slate-200 flex gap-2">
                <a href="{{ route('director.messages.index') }}"
                   class="inline-flex items-center justify-center rounded-xl bg-slate-100 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-200 transition">
                    Fermer
                </a>
            </div>
        </div>
    </div>
</x-director-layout>
