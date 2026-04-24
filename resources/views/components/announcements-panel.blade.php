@props([
    'title' => 'Actualités',
    'lead' => null,
    'announcements' => [],
    'badge' => null,
    'accent' => 'from-indigo-500 to-purple-500',
])

<div class="rounded-[28px] border border-black/5 bg-white/90 backdrop-blur-xl p-6 shadow-lg">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <div>
            <div class="text-sm font-semibold text-slate-900">{{ $title }}</div>
            @if($lead)
                <p class="text-xs text-slate-500">{{ $lead }}</p>
            @endif
        </div>
        @if($badge)
            <span class="rounded-full bg-gradient-to-r {{ $accent }} px-3 py-1 text-xs font-semibold text-white">
                {{ $badge }}
            </span>
        @endif
    </div>

    <div class="mt-5 space-y-4">
        @forelse($announcements as $news)
            <div class="group flex flex-col gap-2 rounded-2xl border border-white/70 bg-slate-900/5 px-4 py-3 shadow-sm transition hover:border-white/80 hover:shadow-xl">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <div class="text-sm font-semibold text-slate-900 flex items-center gap-2">
                            {{ $news['title'] ?? 'Annonce' }}
                            @if(!empty($news['notify']))
                                <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            @endif
                        </div>
                        <p class="text-xs font-semibold text-slate-500">{{ $news['tag'] ?? 'Info' }}</p>
                    </div>
                    <span class="text-[11px] text-slate-500">{{ $news['timestamp'] ?? '' }}</span>
                </div>
                <p class="text-sm text-slate-700 leading-relaxed">{{ $news['text'] ?? 'Aucune description.' }}</p>
                @if(!empty($news['recipients']))
                    <div class="flex flex-wrap gap-2 text-[11px] font-semibold text-slate-500">
                        @foreach($news['recipients'] as $recipient)
                            <span class="rounded-full border border-white/50 bg-white/80 px-3 py-0.5">{{ $recipient }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-3 text-sm text-slate-500">
                Aucune actualité disponible pour le moment.
            </div>
        @endforelse
    </div>
</div>
