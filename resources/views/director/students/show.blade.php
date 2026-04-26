<x-director-layout title="Suivi de l eleve">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">{{ $student->full_name }}</h1>
            <p class="mt-1 text-sm text-slate-500">
                {{ $student->classroom?->name ?? '—' }} • {{ $student->classroom?->level?->name ?? '—' }}
            </p>
        </div>

        <a href="{{ route('director.students.index') }}"
           class="rounded-2xl border border-black/10 bg-white px-4 py-2 text-sm font-semibold hover:bg-white/80">
            ← Retour
        </a>
    </div>

    <div class="mt-6 grid grid-cols-1 xl:grid-cols-3 gap-4">
        {{-- Notes --}}
        <div class="xl:col-span-1 rounded-[28px] border border-black/5 bg-white/70 p-6 shadow-[0_18px_45px_-35px_rgba(0,0,0,.45)]">
            <div class="text-sm font-semibold text-slate-900">Notes pedagogiques</div>
            <p class="mt-1 text-xs text-slate-500">Reserve au suivi pedagogique de la direction.</p>

            <form method="POST" action="{{ route('director.students.notes.store', $student) }}" class="mt-4">
                @csrf
                <textarea name="note" rows="4" class="w-full rounded-2xl border border-black/10 bg-white/70 px-4 py-3 text-sm"
                          placeholder="Ajouter une note de suivi..."></textarea>
                <button class="mt-2 w-full rounded-2xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-black">
                    Ajouter la note
                </button>
            </form>

            <div class="mt-4 space-y-3">
                @forelse($student->notes->sortByDesc('created_at') as $n)
                    <div class="rounded-2xl border border-black/5 bg-white/60 p-4">
                        <div class="text-xs text-slate-500">
                            {{ optional($n->created_at)->format('Y-m-d H:i') }}
                            • {{ $n->author?->name ?? '—' }}
                        </div>
                        <div class="mt-2 text-sm text-slate-800 whitespace-pre-line">{{ $n->note }}</div>
                    </div>
                @empty
                    <div class="rounded-2xl border border-black/5 bg-white/60 p-4 text-sm text-slate-600">
                        Aucune note pour le moment.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Courses --}}
        <div class="xl:col-span-1 rounded-[28px] border border-black/5 bg-white/70 p-6 shadow-[0_18px_45px_-35px_rgba(0,0,0,.45)]">
            <div class="flex items-center justify-between">
                <div class="text-sm font-semibold text-slate-900">Derniers cours</div>
                <div class="text-xs text-slate-500">{{ $courses->count() }}</div>
            </div>

            <div class="mt-4 space-y-3">
                @forelse($courses as $c)
                    <div class="rounded-2xl border border-black/5 bg-white/60 p-4">
                        <div class="text-sm font-semibold text-slate-900">{{ $c->title }}</div>
                        <div class="mt-1 text-xs text-slate-500">
                            {{ optional($c->created_at)->format('Y-m-d H:i') }} @if($c->teacher) • {{ $c->teacher->name }} @endif
                        </div>
                        @if($c->description)
                            <p class="mt-2 text-sm text-slate-700">{{ $c->description }}</p>
                        @endif

                        @if($c->attachments?->count())
                            <div class="mt-3 text-xs text-slate-500">Pieces jointes : {{ $c->attachments->count() }}</div>
                        @endif
                    </div>
                @empty
                    <div class="rounded-2xl border border-black/5 bg-white/60 p-4 text-sm text-slate-600">Aucun cours pour le moment.</div>
                @endforelse
            </div>
        </div>

        {{-- Homeworks --}}
        <div class="xl:col-span-1 rounded-[28px] border border-black/5 bg-white/70 p-6 shadow-[0_18px_45px_-35px_rgba(0,0,0,.45)]">
            <div class="flex items-center justify-between">
                <div class="text-sm font-semibold text-slate-900">Derniers devoirs</div>
                <div class="text-xs text-slate-500">{{ $homeworks->count() }}</div>
            </div>

            <div class="mt-4 space-y-3">
                @forelse($homeworks as $h)
                    <div class="rounded-2xl border border-black/5 bg-white/60 p-4">
                        <div class="text-sm font-semibold text-slate-900">{{ $h->title }}</div>
                        <div class="mt-1 text-xs text-slate-500">
                            {{ optional($h->created_at)->format('Y-m-d H:i') }} @if($h->teacher) • {{ $h->teacher->name }} @endif
                            @if(!empty($h->due_at)) • A rendre le : {{ $h->due_at }} @endif
                        </div>
                        @if($h->description)
                            <p class="mt-2 text-sm text-slate-700">{{ $h->description }}</p>
                        @endif
                    </div>
                @empty
                    <div class="rounded-2xl border border-black/5 bg-white/60 p-4 text-sm text-slate-600">Aucun devoir pour le moment.</div>
                @endforelse
            </div>
        </div>
    </div>
</x-director-layout>
