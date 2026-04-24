<x-parent-layout title="Devoirs" subtitle="Suivez les devoirs publies pour vos enfants, leurs echeances et les documents a telecharger.">
    <section class="student-panel">
        <form method="GET" data-loading-label="Recherche des devoirs..." class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_260px_180px]">
            <input
                name="q"
                value="{{ $q }}"
                placeholder="Rechercher un devoir..."
                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm"
            >
            <select name="child_id" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                <option value="">Tous mes enfants</option>
                @foreach($children as $child)
                    <option value="{{ $child->id }}" @selected((string) $childId === (string) $child->id)>
                        {{ $child->full_name }} - {{ $child->classroom?->name ?? '-' }}
                    </option>
                @endforeach
            </select>
            <button class="app-button-primary rounded-2xl px-4 py-3">Filtrer</button>
        </form>
    </section>

    <section class="mt-6 space-y-4">
        @if($homeworks instanceof \Illuminate\Support\Collection)
            <div class="student-empty">Aucun devoir disponible car aucun enfant n est rattache a une classe exploitable.</div>
        @elseif($homeworks->isEmpty())
            <div class="student-empty">Aucun devoir publie ne correspond a votre filtre actuel.</div>
        @else
            @foreach($homeworks as $homework)
                <article class="student-panel">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-xl font-semibold text-slate-950">{{ $homework->title }}</h2>
                                <span class="student-chip">{{ $homework->classroom?->name ?? '-' }}</span>
                                @if($homework->classroom?->level?->name)
                                    <span class="student-chip">{{ $homework->classroom->level->name }}</span>
                                @endif
                            </div>
                            <p class="mt-2 text-sm text-slate-500">
                                {{ $homework->teacher?->name ?? 'Enseignant non renseigne' }}
                                @if($homework->subject?->name)
                                    <span class="mx-2 text-slate-300">|</span>
                                    {{ $homework->subject->name }}
                                @endif
                                <span class="mx-2 text-slate-300">|</span>
                                Echeance {{ $homework->due_at?->format('d/m/Y H:i') ?? '-' }}
                            </p>
                            @if($homework->description)
                                <p class="mt-4 text-sm leading-7 text-slate-700">{{ $homework->description }}</p>
                            @endif
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-right">
                            <p class="text-[0.72rem] font-semibold uppercase tracking-[0.16em] text-slate-500">Fichiers</p>
                            <p class="mt-2 text-2xl font-semibold text-slate-950">{{ $homework->attachments->count() }}</p>
                        </div>
                    </div>

                    @if($homework->attachments->isNotEmpty())
                        <div class="mt-5 rounded-3xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Pieces jointes</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach($homework->attachments as $attachment)
                                    <a href="{{ route('parent.homeworks.attachments.download', $attachment) }}" data-no-loading="true" class="app-button-secondary rounded-full px-4 py-2 text-xs font-semibold">
                                        {{ $attachment->original_name }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="student-empty mt-5 px-4 py-6">
                            Aucun fichier joint pour ce devoir.
                        </div>
                    @endif
                </article>
            @endforeach

            <div class="mt-5">
                {{ $homeworks->links() }}
            </div>
        @endif
    </section>
</x-parent-layout>
