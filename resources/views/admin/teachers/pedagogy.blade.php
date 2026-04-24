<x-admin-layout title="Affectation pedagogique">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Affectation pedagogique</h1>
            <p class="mt-1 text-sm text-slate-500">Affectez des classes et des matieres a chaque enseignant, puis centralisez ses ressources.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="mt-6 rounded-3xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-900">
            {{ session('success') }}
        </div>
    @endif

    <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div class="rounded-[28px] border border-black/10 bg-white/80 shadow-sm overflow-hidden">
            <div class="border-b border-black/10 bg-white/60 px-5 py-4 flex items-center justify-between">
                <div>
                    <div class="text-sm font-semibold text-slate-900">Enseignants</div>
                    <div class="text-xs text-slate-500">Choisissez un enseignant pour gerer ses classes, ses matieres et ses ressources.</div>
                </div>
                <div class="text-xs text-slate-500">Total : <span class="font-semibold text-slate-900">{{ count($teachers ?? []) }}</span></div>
            </div>

            <div class="p-4 space-y-2">
                @forelse(($teachers ?? []) as $t)
                    <a href="{{ route('admin.teachers.pedagogy', ['teacher_id' => $t->id]) }}"
                       class="flex items-center justify-between rounded-2xl border border-black/10 bg-white px-4 py-3 hover:bg-slate-50 transition {{ (string)request('teacher_id') === (string)$t->id ? 'ring-4 ring-black/10' : '' }}">
                        <div>
                            <div class="text-sm font-semibold text-slate-900">{{ $t->name }}</div>
                            <div class="text-xs text-slate-500">{{ $t->email ?? '-' }}</div>
                        </div>
                        <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">ID: {{ $t->id }}</span>
                    </a>
                @empty
                    <div class="p-8 text-center text-slate-500">Aucun enseignant trouve.</div>
                @endforelse
            </div>
        </div>

        <div class="rounded-[28px] border border-black/10 bg-white/80 shadow-sm overflow-hidden">
            <div class="border-b border-black/10 bg-white/60 px-5 py-4">
                <div class="text-sm font-semibold text-slate-900">Affectation</div>
                <div class="text-xs text-slate-500">Classes et matieres enseignees.</div>
            </div>

            <div class="p-6">
                @if(empty($selectedTeacher))
                    <div class="rounded-3xl border border-amber-200 bg-amber-50 p-4 text-amber-900">Choisissez un enseignant a gauche pour gerer son affectation.</div>
                @else
                    <div class="mb-5 flex items-start justify-between gap-3">
                        <div>
                            <div class="text-sm font-semibold text-slate-900">{{ $selectedTeacher->name }}</div>
                            <div class="text-xs text-slate-500">{{ $selectedTeacher->email ?? '-' }}</div>
                        </div>
                        <div class="flex gap-2">
                            <span class="inline-flex items-center rounded-full bg-black/5 px-3 py-1 text-xs font-semibold text-slate-700">{{ count($assignedClassroomIds ?? []) }} classes</span>
                            <span class="inline-flex items-center rounded-full bg-black/5 px-3 py-1 text-xs font-semibold text-slate-700">{{ count($assignedSubjectIds ?? []) }} matieres</span>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.teachers.pedagogy.update', $selectedTeacher) }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-sm font-semibold">Classes affectees</label>
                            </div>
                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                @forelse(($classrooms ?? []) as $c)
                                    <label class="flex items-center gap-2 rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm hover:bg-slate-50">
                                        <input type="checkbox" name="classrooms[]" value="{{ $c->id }}" @checked(in_array($c->id, $assignedClassroomIds ?? [], true)) class="rounded border-black/20">
                                        <span class="font-semibold text-slate-800">{{ $c->name ?? ('Classe #' . $c->id) }}</span>
                                    </label>
                                @empty
                                    <div class="text-sm text-slate-500">Aucune classe trouvee.</div>
                                @endforelse
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-sm font-semibold">Matieres enseignees</label>
                            </div>
                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                @forelse(($subjects ?? []) as $s)
                                    <label class="flex items-center gap-2 rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm hover:bg-slate-50">
                                        <input type="checkbox" name="subjects[]" value="{{ $s->id }}" @checked(in_array($s->id, $assignedSubjectIds ?? [], true)) class="rounded border-black/20">
                                        <span class="font-semibold text-slate-800">{{ $s->name ?? ('Matiere #' . $s->id) }}</span>
                                    </label>
                                @empty
                                    <div class="text-sm text-slate-500">Aucune matiere trouvee.</div>
                                @endforelse
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-2">
                            <button class="rounded-2xl bg-black px-6 py-2.5 text-sm font-semibold text-white hover:bg-slate-900">Enregistrer</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>

    @if(!empty($selectedTeacher))
        <div class="mt-6 grid gap-4 xl:grid-cols-[380px_minmax(0,1fr)]">
            <div class="rounded-[28px] border border-black/10 bg-white/80 p-6 shadow-sm">
                <div>
                    <div class="text-sm font-semibold text-slate-900">Ressources pedagogiques</div>
                    <div class="mt-1 text-xs text-slate-500">Deposez les supports relies a l enseignant, une matiere et une classe.</div>
                </div>

                <form method="POST" action="{{ route('admin.teachers.pedagogy.resources.store', $selectedTeacher) }}" enctype="multipart/form-data" class="mt-5 space-y-4">
                    @csrf
                    <div>
                        <label class="mb-1 block text-sm font-semibold">Titre</label>
                        <input name="title" class="w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5" required>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold">Description</label>
                        <textarea name="description" rows="3" class="w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5"></textarea>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold">Matiere</label>
                        <select name="subject_id" class="w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5">
                            <option value="">Sans matiere precise</option>
                            @foreach(($subjects ?? []) as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold">Classe</label>
                        <select name="classroom_id" class="w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5">
                            <option value="">Toutes / non precise</option>
                            @foreach(($classrooms ?? []) as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold">Fichier</label>
                        <input type="file" name="resource" class="w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5" required>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="is_active" value="1" checked class="rounded border-black/20">
                        Ressource active
                    </label>
                    <button class="rounded-2xl bg-black px-6 py-2.5 text-sm font-semibold text-white hover:bg-slate-900">Ajouter la ressource</button>
                </form>
            </div>

            <div class="rounded-[28px] border border-black/10 bg-white/80 p-6 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <div class="text-sm font-semibold text-slate-900">Bibliotheque de l enseignant</div>
                        <div class="mt-1 text-xs text-slate-500">Classement par matiere et classe, sans sortir du module pedagogique.</div>
                    </div>
                    <div class="text-xs text-slate-500">{{ count($resources ?? []) }} fichier(s)</div>
                </div>

                <div class="mt-5 space-y-3">
                    @forelse(($resources ?? []) as $resource)
                        <article class="rounded-2xl border border-black/10 bg-slate-50 px-4 py-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="text-sm font-semibold text-slate-900">{{ $resource->title }}</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ $resource->subject?->name ?? 'Sans matiere' }} • {{ $resource->classroom?->name ?? 'Toutes classes' }}</div>
                                </div>
                                <form method="POST" action="{{ route('admin.teachers.pedagogy.resources.destroy', [$selectedTeacher, $resource]) }}" onsubmit="return confirm('Supprimer cette ressource ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100">Supprimer</button>
                                </form>
                            </div>
                            @if($resource->description)
                                <p class="mt-3 text-sm text-slate-600">{{ $resource->description }}</p>
                            @endif
                            <div class="mt-3">
                                <a href="{{ $resource->file_url }}" target="_blank" rel="noopener" class="text-xs font-semibold text-slate-700 hover:underline">Ouvrir le fichier</a>
                            </div>
                        </article>
                    @empty
                        <div class="rounded-2xl border border-dashed border-black/10 px-4 py-8 text-center text-sm text-slate-500">Aucune ressource pedagogique enregistree pour cet enseignant.</div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif
</x-admin-layout>
