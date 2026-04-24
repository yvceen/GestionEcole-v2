<x-teacher-layout title="Devoirs">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Devoirs</h1>
            <p class="mt-1 text-sm text-slate-500">Devoirs publies par l'enseignant.</p>
        </div>
        <a href="{{ route('teacher.homeworks.create') }}" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-black">
            Nouveau devoir
        </a>
    </div>

    <div class="mt-6 space-y-3">
        @forelse($homeworks as $hw)
            <div class="rounded-2xl border border-black/10 bg-white p-4">
                <div class="text-base font-semibold text-slate-900">{{ $hw->title }}</div>
                <div class="mt-1 text-xs text-slate-500">
                    {{ $hw->classroom?->name ?? '-' }}
                    @if($hw->due_at)
                        - Echeance {{ $hw->due_at->format('Y-m-d H:i') }}
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-black/10 bg-white p-4 text-sm text-slate-600">Aucun devoir.</div>
        @endforelse
    </div>

    <div class="mt-6">{{ $homeworks->links() }}</div>
</x-teacher-layout>
