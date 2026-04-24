<x-student-layout title="Mes cours" subtitle="Retrouvez les contenus publies pour votre classe, avec acces direct aux pieces jointes utiles.">
    <section class="student-panel">
        <form method="GET" data-loading-label="Recherche des cours..." class="grid gap-3 md:grid-cols-[minmax(0,1fr)_180px]">
            <input
                name="q"
                value="{{ $q }}"
                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-slate-400 focus:ring-slate-400"
                placeholder="Rechercher un cours..."
            />
            <button class="app-button-primary rounded-2xl px-4 py-3">
                Rechercher
            </button>
        </form>
    </section>

    <section class="mt-6 space-y-4">
        @forelse($courses as $course)
            <article class="student-panel">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0">
                        <h2 class="text-xl font-semibold tracking-tight text-slate-950">{{ $course->title }}</h2>
                        <p class="mt-2 text-sm text-slate-500">
                            {{ $course->classroom?->name ?? '-' }}
                            @if($course->teacher)
                                <span class="mx-2 text-slate-300">|</span>{{ $course->teacher->name }}
                            @endif
                        </p>
                    </div>

                    <div class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700">
                        {{ $course->created_at?->format('d/m/Y') ?? '-' }}
                    </div>
                </div>

                @if($course->description)
                    <p class="mt-4 text-sm leading-7 text-slate-700">{{ $course->description }}</p>
                @endif

                @if($course->attachments && $course->attachments->count())
                    <div class="mt-5 rounded-3xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Fichiers joints</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach($course->attachments as $attachment)
                                <a
                                    href="{{ route('student.courses.attachments.download', $attachment) }}"
                                    data-no-loading="true"
                                    class="inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
                                >
                                    {{ $attachment->original_name }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </article>
        @empty
            <div class="student-empty">
                Aucun cours publie pour votre classe.
            </div>
        @endforelse
    </section>

    <div class="mt-5">
        {{ $courses->links() }}
    </div>
</x-student-layout>
