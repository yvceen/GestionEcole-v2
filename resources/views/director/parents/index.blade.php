<x-director-layout title="Parents">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Parents</h1>
            <p class="mt-1 text-sm text-slate-500">Recherchez rapidement un parent, filtrez par classe et reperez les enfants rattaches en un coup d oeil.</p>
        </div>

        <form method="GET" class="grid gap-3 rounded-[28px] border border-black/5 bg-white/70 p-4 shadow-[0_18px_45px_-35px_rgba(0,0,0,.45)] md:grid-cols-[minmax(0,1fr)_220px_minmax(0,1fr)_auto]">
            <input name="q" value="{{ $q }}" placeholder="Nom du parent, email ou telephone..."
                   class="rounded-2xl border border-black/10 bg-white/70 px-4 py-2 text-sm"/>
            <select name="classroom_id" class="rounded-2xl border border-black/10 bg-white/70 px-4 py-2 text-sm">
                <option value="">Toutes les classes</option>
                @foreach($classrooms as $classroom)
                    <option value="{{ $classroom->id }}" @selected((string) $classroomId === (string) $classroom->id)>{{ $classroom->name }}</option>
                @endforeach
            </select>
            <input name="child_name" value="{{ $childName }}" placeholder="Nom de l enfant..."
                   class="rounded-2xl border border-black/10 bg-white/70 px-4 py-2 text-sm"/>
            <div class="flex gap-2">
                <button class="rounded-2xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-black">Rechercher</button>
                <a href="{{ route('director.parents.index') }}" class="rounded-2xl border border-black/10 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reinitialiser</a>
            </div>
        </form>
    </div>

    <div class="mt-6 space-y-4">
        @if($parents->count() === 0)
            <div class="rounded-2xl border border-black/5 bg-white/70 p-6 text-center text-slate-500">
                Aucun parent ne correspond a ces filtres.
            </div>
        @endif
        @foreach($parents as $p)
            @php $kids = $childrenByParent[$p->id] ?? collect(); @endphp

            <div class="rounded-[28px] border border-black/5 bg-white/70 p-6 shadow-[0_18px_45px_-35px_rgba(0,0,0,.45)]">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="text-lg font-semibold text-slate-900">{{ $p->name }}</div>
                        <div class="mt-1 text-xs text-slate-500">Enfants lies : {{ $kids->count() }}</div>
                    </div>
                    <span class="rounded-full border border-black/10 bg-white px-3 py-1 text-xs font-semibold">#{{ $p->id }}</span>
                </div>

                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                    @forelse($kids as $c)
                        <div class="rounded-2xl border border-black/5 bg-white/60 p-4">
                            <div class="text-sm font-semibold text-slate-900">{{ $c->full_name }}</div>
                            <div class="mt-1 text-xs text-slate-500">
                                {{ $c->classroom?->name ?? '—' }} • {{ $c->classroom?->level?->name ?? '—' }}
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-black/5 bg-white/60 p-4 text-sm text-slate-600">
                            Aucun enfant lie pour le moment.
                        </div>
                    @endforelse
                </div>
            </div>
        @endforeach

        {{ $parents->links() }}
    </div>
</x-director-layout>
