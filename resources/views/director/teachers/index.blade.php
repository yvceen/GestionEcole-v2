<x-director-layout title="Teachers">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Teacher Management</h1>
            <p class="mt-1 text-sm text-slate-500">Activate/Deactivate + Assign classrooms.</p>
        </div>

        <form method="GET" class="flex flex-col gap-2 sm:flex-row">
            <input name="q" value="{{ $q }}" placeholder="Search by name, email, or phone..."
                   class="min-w-0 rounded-2xl border border-black/10 bg-white/70 px-4 py-2 text-sm"/>
            <button class="rounded-2xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-black">Search</button>
        </form>
    </div>

    <div class="mt-6 grid grid-cols-1 xl:grid-cols-2 gap-4">
        @if($teachers->count() === 0)
            <div class="rounded-2xl border border-black/5 bg-white/70 p-6 text-center text-slate-500">
                No results found.
            </div>
        @endif
        @foreach($teachers as $t)
            <div class="rounded-[28px] border border-black/5 bg-white/70 p-6 shadow-[0_18px_45px_-35px_rgba(0,0,0,.45)]">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="text-lg font-semibold truncate">{{ $t->name }}</div>
                        <div class="mt-1 text-xs text-slate-500">
                            Status:
                            <span class="font-semibold {{ $t->is_active ? 'text-emerald-700' : 'text-rose-700' }}">
                                {{ $t->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>

                        <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-3">
                            <div class="rounded-2xl border border-black/5 bg-white/60 p-3">
                                <div class="text-[11px] uppercase tracking-wider text-slate-500">Courses</div>
                                <div class="mt-1 text-lg font-semibold">{{ $t->courses_count }}</div>
                            </div>
                            <div class="rounded-2xl border border-black/5 bg-white/60 p-3">
                                <div class="text-[11px] uppercase tracking-wider text-slate-500">Homeworks</div>
                                <div class="mt-1 text-lg font-semibold">{{ $t->homeworks_count }}</div>
                            </div>
                            <div class="rounded-2xl border border-black/5 bg-white/60 p-3">
                                <div class="text-[11px] uppercase tracking-wider text-slate-500">Last</div>
                                <div class="mt-1 text-xs font-semibold text-slate-900">
                                    {{ $t->last_activity ? \Carbon\Carbon::parse($t->last_activity)->format('Y-m-d') : '—' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('director.teachers.toggle', $t) }}">
                        @csrf
                        <button class="rounded-2xl px-4 py-2 text-sm font-semibold text-white {{ $t->is_active ? 'bg-rose-600 hover:bg-rose-700' : 'bg-emerald-600 hover:bg-emerald-700' }}">
                            {{ $t->is_active ? 'Deactivate' : 'Activate' }}
                        </button>
                    </form>
                </div>

                <div class="my-4 h-px bg-black/5"></div>

                <form method="POST" action="{{ route('director.teachers.assign', $t) }}">
                    @csrf
                    <div class="text-sm font-semibold text-slate-900">Assign classrooms</div>
                    <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-56 overflow-auto pr-1">
                        @foreach($classrooms as $c)
                            <label class="flex items-center gap-2 rounded-2xl border border-black/5 bg-white/60 px-3 py-2 text-sm">
                                <input type="checkbox" name="classrooms[]" value="{{ $c->id }}"
                                       @checked($t->teacherClassrooms->contains('id',$c->id))
                                       class="rounded border-black/20" />
                                <span class="min-w-0 truncate">
                                    {{ $c->name }} <span class="text-xs text-slate-500">— {{ $c->level?->name }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>

                    <button class="mt-3 w-full rounded-2xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-black">
                        Save assignments
                    </button>
                </form>
            </div>
        @endforeach
    </div>

    <div class="mt-6">
        {{ $teachers->links() }}
    </div>
</x-director-layout>
