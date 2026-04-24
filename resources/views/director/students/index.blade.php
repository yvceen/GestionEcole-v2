<x-director-layout title="Students">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Students</h1>
            <p class="mt-1 text-sm text-slate-500">Filter by level/classroom and open follow-up.</p>
        </div>
    </div>

    <form method="GET" class="mt-5 rounded-[28px] border border-black/5 bg-white/70 p-5 shadow-[0_18px_45px_-35px_rgba(0,0,0,.45)]">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
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

            <input name="q" value="{{ $q }}" placeholder="Search by name, email, or phone..."
                   class="rounded-2xl border border-black/10 bg-white/70 px-4 py-2 text-sm"/>

            <button class="rounded-2xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-black">
                Filter
            </button>
        </div>
    </form>

    <div class="mt-5 rounded-[28px] border border-black/5 bg-white/70 p-2 shadow-[0_18px_45px_-35px_rgba(0,0,0,.45)]">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-xs text-slate-500">
                    <tr>
                        <th class="px-4 py-3 text-left">Student</th>
                        <th class="px-4 py-3 text-left">Classroom</th>
                        <th class="px-4 py-3 text-left">Level</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-black/5">
                    @forelse($students as $s)
                        <tr>
                            <td class="px-4 py-3 font-semibold text-slate-900">{{ $s->full_name }}</td>
                            <td class="px-4 py-3">{{ $s->classroom?->name ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $s->classroom?->level?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('director.students.show', $s) }}"
                                   class="rounded-2xl bg-slate-900 px-4 py-2 text-xs font-semibold text-white hover:bg-black">
                                    Open follow-up →
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-slate-500">No results found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4">
            {{ $students->links() }}
        </div>
    </div>
</x-director-layout>
