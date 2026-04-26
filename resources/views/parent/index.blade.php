<x-parent-layout title="Devoirs">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Devoirs</h1>
            <p class="mt-1 text-sm text-slate-500">Retrouvez les devoirs, les fichiers partages et le suivi utile pour votre enfant.</p>
        </div>

        <a href="{{ route('parent.dashboard') }}"
           class="inline-flex items-center justify-center rounded-2xl border border-black/5 bg-white/70 px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-white transition">
            ← Retour au tableau de bord
        </a>
    </div>

    @if($homeworks->isEmpty())
            <div class="mt-6 rounded-[30px] border border-black/5 bg-white/70 p-6 shadow-[0_22px_60px_-40px_rgba(0,0,0,.50)]">
                <div class="text-base font-semibold text-slate-900">Pas de devoirs pour le moment</div>
            <p class="mt-1 text-sm text-slate-600">Les nouveaux devoirs apparaitront ici des qu ils seront publies.</p>
        </div>
    @else
        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach($homeworks as $hw)
                <a href="{{ route('parent.homeworks.show', $hw) }}"
                   class="group rounded-[30px] border border-black/5 bg-white/70 p-6 shadow-[0_22px_60px_-40px_rgba(0,0,0,.50)] hover:bg-white transition">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="text-base font-semibold text-slate-900 group-hover:underline">
                                {{ $hw->title }}
                            </div>
                            <div class="mt-1 text-xs text-slate-500">
                                {{ $hw->classroom?->name ?? '—' }} • {{ $hw->classroom?->level?->name ?? '—' }}
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-[11px] uppercase tracking-wider text-slate-500">Echeance</div>
                            <div class="text-sm font-semibold text-slate-900">
                                {{ $hw->due_at?->format('d/m/Y') ?? '—' }}
                            </div>
                        </div>
                    </div>

                    <p class="mt-3 text-sm text-slate-600 line-clamp-3">
                        {{ $hw->description ?? '—' }}
                    </p>

                    <div class="mt-4 flex items-center justify-between text-xs text-slate-500">
                        <span>{{ $hw->files->count() }} fichier(s)</span>
                        <span class="font-semibold">Voir →</span>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $homeworks->links() }}
        </div>
    @endif
</x-parent-layout>
