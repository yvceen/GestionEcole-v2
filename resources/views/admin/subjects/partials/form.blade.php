@php
    $selectedTeacherIds = collect(old('teacher_ids', $assignedTeacherIds ?? []))
        ->map(fn ($id) => (int) $id)
        ->all();
    $routePrefix = $routePrefix ?? 'admin.subjects';
@endphp

@if($errors->any())
    <div class="rounded-3xl border border-red-200 bg-red-50 p-4 text-red-900">
        <ul class="ml-5 list-disc">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid gap-6 xl:grid-cols-[minmax(0,1.3fr)_minmax(320px,0.9fr)]">
    <section class="rounded-[28px] border border-black/10 bg-white/80 p-6 shadow-sm">
        <div class="grid gap-5 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-semibold">Nom de la matiere</label>
                <input name="name" value="{{ old('name', $subject->name) }}"
                       class="w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5" required>
            </div>

            <div>
                <label class="mb-1 block text-sm font-semibold">Code</label>
                <input name="code" value="{{ old('code', $subject->code) }}"
                       class="w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5"
                       placeholder="MAT, SCI, FR...">
            </div>

            <div class="flex items-center gap-2 pt-7">
                <input id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $subject->is_active ?? true))
                       class="rounded border-black/20">
                <label for="is_active" class="text-sm text-slate-700">Matiere active</label>
            </div>
        </div>
    </section>

    <section class="rounded-[28px] border border-black/10 bg-white/80 p-6 shadow-sm">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Affectation enseignants</h2>
                <p class="mt-1 text-sm text-slate-500">La meme table `teacher_subjects` continue d etre utilisee par les devoirs, notes et emplois du temps.</p>
            </div>
                    @if(Route::has('admin.teachers.pedagogy'))
                        <a href="{{ route('admin.teachers.pedagogy') }}" class="text-xs font-semibold text-slate-600 hover:underline">
                            Affectations classes
                        </a>
                    @endif
        </div>

        <div class="mt-5 space-y-3">
            @forelse($teachers as $teacher)
                <label class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 px-4 py-3 hover:border-slate-300">
                    <span>
                        <span class="block text-sm font-semibold text-slate-900">{{ $teacher->name }}</span>
                        <span class="text-xs text-slate-500">Enseignant</span>
                    </span>
                    <input
                        type="checkbox"
                        name="teacher_ids[]"
                        value="{{ $teacher->id }}"
                        class="rounded border-slate-300"
                        @checked(in_array((int) $teacher->id, $selectedTeacherIds, true))
                    >
                </label>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 px-4 py-6 text-sm text-slate-500">
                    Aucun enseignant disponible dans cette ecole.
                </div>
            @endforelse
        </div>
    </section>
</div>

<div class="flex items-center justify-end gap-3">
    <a href="{{ route($routePrefix . '.index') }}"
       class="rounded-2xl border border-black/10 bg-white px-5 py-2.5 text-sm font-semibold hover:bg-slate-50">
        Annuler
    </a>
    <button class="rounded-2xl bg-black px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-900">
        {{ $subject->exists ? 'Enregistrer' : 'Creer' }}
    </button>
</div>
