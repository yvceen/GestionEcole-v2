<x-director-layout title="Monitoring">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Monitoring</h1>
            <p class="mt-1 text-sm text-slate-500">Filter by Level / Classroom / Teacher / Date.</p>
        </div>
    </div>

    <form method="GET" class="mt-5 rounded-[28px] border border-black/5 bg-white/70 p-5 shadow-[0_18px_45px_-35px_rgba(0,0,0,.45)]">
        <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
            <select name="type" class="rounded-2xl border border-black/10 bg-white/70 px-3 py-2 text-sm">
                <option value="courses" @selected($type==='courses')>Courses</option>
                <option value="homeworks" @selected($type==='homeworks')>Homeworks</option>
            </select>

            <select name="level_id" class="rounded-2xl border border-black/10 bg-white/70 px-3 py-2 text-sm">
                <option value="">All levels</option>
                @foreach($levels as $l)
                    <option value="{{ $l->id }}" @selected((string)$levelId === (string)$l->id)>{{ $l->name }}</option>
                @endforeach
            </select>

            <select name="classroom_id" class="rounded-2xl border border-black/10 bg-white/70 px-3 py-2 text-sm">
                <option value="">All classrooms</option>
                @foreach($classrooms as $c)
                    <option value="{{ $c->id }}" @selected((string)$classroomId === (string)$c->id)>{{ $c->name }}</option>
                @endforeach
            </select>

            <select name="teacher_id" class="rounded-2xl border border-black/10 bg-white/70 px-3 py-2 text-sm">
                <option value="">All teachers</option>
                @foreach($teachers as $t)
                    <option value="{{ $t->id }}" @selected((string)$teacherId === (string)$t->id)>{{ $t->name }}</option>
                @endforeach
            </select>

            <input type="date" name="from" value="{{ request('from') }}" class="rounded-2xl border border-black/10 bg-white/70 px-3 py-2 text-sm"/>
            <input type="date" name="to" value="{{ request('to') }}" class="rounded-2xl border border-black/10 bg-white/70 px-3 py-2 text-sm"/>

            <div class="md:col-span-4">
                <input name="q" value="{{ $q }}" placeholder="Search title/description…"
                       class="w-full rounded-2xl border border-black/10 bg-white/70 px-4 py-2 text-sm"/>
            </div>

            <div class="md:col-span-2 flex gap-2">
                <button class="w-full rounded-2xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-black">
                    Apply
                </button>
                <a href="{{ route('director.monitoring') }}"
                   class="w-full text-center rounded-2xl border border-black/10 bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-white/80">
                    Reset
                </a>
            </div>
        </div>
    </form>

    <div class="mt-5 space-y-3">
        @foreach($items as $it)
            <div class="rounded-[28px] border border-black/5 bg-white/70 p-6 shadow-[0_18px_45px_-35px_rgba(0,0,0,.45)]">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap gap-2 items-center">
                            <div class="text-lg font-semibold truncate">{{ $it->title }}</div>
                            <span class="rounded-full border border-black/10 bg-white px-3 py-1 text-xs font-semibold">
                                {{ $it->classroom?->name ?? 'Classroom' }}
                            </span>
                            <span class="rounded-full border border-black/10 bg-white px-3 py-1 text-xs font-semibold">
                                {{ $it->classroom?->level?->name ?? 'Level' }}
                            </span>
                        </div>
                        <div class="mt-1 text-xs text-slate-500">
                            {{ optional($it->created_at)->format('Y-m-d H:i') }}
                            @if($it->teacher) • {{ $it->teacher->name }} @endif
                        </div>

                        @if($it->description)
                            <p class="mt-3 text-sm text-slate-700 leading-relaxed">{{ $it->description }}</p>
                        @endif
                    </div>

                    <div class="shrink-0">
                        @if($type==='courses')
                            <div class="rounded-2xl border border-black/5 bg-white/60 px-4 py-3 text-xs text-slate-700">
                                <div class="font-semibold text-slate-900">Attachments</div>
                                <div class="mt-1">{{ $it->attachments?->count() ?? 0 }}</div>
                            </div>
                        @else
                            @if(!empty($it->due_at))
                                <div class="rounded-2xl border border-black/5 bg-white/60 px-4 py-3 text-xs text-slate-700">
                                    <div class="font-semibold text-slate-900">Due</div>
                                    <div class="mt-1">{{ $it->due_at }}</div>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        @endforeach

        <div class="mt-6">
            {{ $items->links() }}
        </div>
    </div>
</x-director-layout>
