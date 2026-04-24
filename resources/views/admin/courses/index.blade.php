<x-admin-layout title="Cours">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Cours</h1>
            <p class="mt-1 text-sm text-slate-500">Cours de l'ecole.</p>
        </div>
        <a href="{{ route('admin.courses.create') }}" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-black">
            Nouveau cours
        </a>
    </div>

    <form method="GET" class="mt-4">
        <input name="q" value="{{ $q }}" placeholder="Rechercher..." class="w-full max-w-sm rounded-xl border-black/10">
    </form>

    <div class="mt-6 space-y-3">
        @forelse($courses as $course)
            <div class="rounded-2xl border border-black/10 bg-white p-4">
                <div class="text-base font-semibold text-slate-900">{{ $course->title }}</div>
                <div class="mt-1 text-xs text-slate-500">
                    {{ $course->classroom?->name ?? '-' }}
                    @if($course->teacher)
                        - {{ $course->teacher->name }}
                    @endif
                </div>
                @php
                    $status = strtolower((string) ($course->status ?? 'pending'));
                    $normalized = match($status) {
                        'approved', 'confirmed' => 'approved',
                        'rejected', 'archived', 'cancelled' => 'rejected',
                        default => 'pending',
                    };
                    $badge = match($normalized) {
                        'approved' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                        'rejected' => 'border-rose-200 bg-rose-50 text-rose-700',
                        default => 'border-amber-200 bg-amber-50 text-amber-700',
                    };
                @endphp
                <div class="mt-2 flex items-center gap-2">
                    <span class="inline-flex rounded-xl border px-2 py-1 text-xs font-semibold {{ $badge }}">{{ strtoupper($normalized) }}</span>
                    @if($normalized === 'pending' && Route::has('admin.courses.approve'))
                        <form method="POST" action="{{ route('admin.courses.approve', $course) }}">
                            @csrf
                            <button class="rounded-xl bg-slate-900 px-2.5 py-1 text-xs font-semibold text-white hover:bg-black">Approve</button>
                        </form>
                        @if(Route::has('admin.courses.reject'))
                            <form method="POST" action="{{ route('admin.courses.reject', $course) }}">
                                @csrf
                                <button class="rounded-xl bg-rose-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-rose-700">Reject</button>
                            </form>
                        @endif
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-black/10 bg-white p-4 text-sm text-slate-600">Aucun cours.</div>
        @endforelse
    </div>

    <div class="mt-6">{{ $courses->links() }}</div>
</x-admin-layout>
