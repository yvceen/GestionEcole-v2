<x-director-layout title="Fiche élève">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">{{ $student->full_name }}</h1>
            <p class="text-sm text-slate-500 mt-1">
                Classe: <span class="font-semibold text-slate-900">{{ $student->classroom?->name ?? '-' }}</span>
            </p>
        </div>

        <a href="{{ route('director.students.index') }}"
           class="text-sm font-semibold text-slate-700 hover:underline">
            ← Retour
        </a>
    </div>

    <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="rounded-[28px] border border-black/10 bg-white/80 p-5">
            <div class="text-sm font-semibold text-slate-900 mb-3">Notes</div>

            @forelse($student->grades as $g)
                <div class="flex items-center justify-between py-2 border-b border-black/5">
                    <div class="text-sm text-slate-700">{{ $g->subject?->name ?? '-' }}</div>
                    <div class="text-sm font-semibold text-slate-900">{{ $g->score }}/{{ $g->max_score }}</div>
                </div>
            @empty
                <div class="text-sm text-slate-500">Aucune note.</div>
            @endforelse
        </div>

        <div class="rounded-[28px] border border-black/10 bg-white/80 p-5">
            <div class="text-sm font-semibold text-slate-900 mb-3">Absences</div>

            @forelse($student->attendances as $a)
                <div class="flex items-center justify-between py-2 border-b border-black/5">
                    <div class="text-sm text-slate-700">{{ $a->date->format('Y-m-d') }}</div>
                    <div class="text-xs font-semibold px-3 py-1 rounded-full
                        {{ $a->status === 'absent' ? 'bg-red-100 text-red-700' : ($a->status === 'late' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700') }}">
                        {{ strtoupper($a->status) }}
                    </div>
                </div>
            @empty
                <div class="text-sm text-slate-500">Aucune absence.</div>
            @endforelse
        </div>
    </div>
</x-director-layout>
