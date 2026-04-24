<x-teacher-layout title="Évaluations">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Évaluations</h1>
            <p class="text-sm text-slate-500 mt-1">جدولة الفروض والامتحانات</p>
        </div>

        <a href="{{ route('teacher.assessments.create') }}"
           class="rounded-2xl bg-black text-white px-5 py-2.5 text-sm font-semibold hover:bg-slate-900">
            + Nouvelle évaluation
        </a>
    </div>

    @if(session('success'))
        <div class="mt-5 rounded-3xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-900">
            {{ session('success') }}
        </div>
    @endif

    <div class="mt-6 grid gap-4">
        @foreach($assessments as $a)
            <div class="rounded-[28px] border border-black/10 bg-white/80 p-5">
                <div class="font-semibold text-slate-900">{{ $a->title }}</div>
                <div class="text-sm text-slate-600 mt-1">
                    Date: <span class="font-semibold">{{ $a->date->format('Y-m-d') }}</span> • Note /{{ $a->max_score }}
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-6">
        {{ $assessments->links() }}
    </div>
</x-teacher-layout>
