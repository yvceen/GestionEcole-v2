{{-- resources/views/teacher/courses/create.blade.php --}}
<x-teacher-layout title="Ajouter un cours">
    <x-slot name="header">Ajouter un cours</x-slot>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 py-6">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-slate-900">Ajouter un cours</h1>
                <p class="mt-1 text-sm text-slate-500">
                    Un bon cours = titre clair + description courte + fichiers bien nommés.
                </p>
            </div>

            @if(\Illuminate\Support\Facades\Route::has('teacher.courses.index'))
                <a href="{{ route('teacher.courses.index') }}"
                   class="rounded-2xl border border-black/10 bg-white px-5 py-2.5 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                    ← Retour
                </a>
            @endif
        </div>

        <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-4">
            {{-- Form --}}
            <div class="lg:col-span-2 rounded-[28px] border border-black/10 bg-white/80 backdrop-blur-xl p-6 shadow-sm">
                <form class="space-y-4" method="POST" action="{{ route('teacher.courses.store') }}" enctype="multipart/form-data">
                    @csrf

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Classe</label>
                        <select name="classroom_id" class="w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm">
                            @foreach($classrooms as $cl)
                                <option value="{{ $cl->id }}" @selected((string)old('classroom_id') === (string)$cl->id)>
                                    {{ $cl->name }} @if($cl->level?->name) — {{ $cl->level->name }} @endif
                                </option>
                            @endforeach
                        </select>
                        @error('classroom_id')
                            <div class="text-sm text-rose-600 mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Titre</label>
                        <input
                            name="title"
                            value="{{ old('title') }}"
                            placeholder="Ex : Chapitre 1 — Les fractions"
                            class="w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm"
                        />
                        @error('title')
                            <div class="text-sm text-rose-600 mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Description (recommandée)</label>
                        <textarea
                            name="description"
                            rows="5"
                            placeholder="Objectifs, résumé, consignes, liens…"
                            class="w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm"
                        >{{ old('description') }}</textarea>
                        @error('description')
                            <div class="text-sm text-rose-600 mt-1">{{ $message }}</div>
                        @enderror
                        <div class="mt-1 text-xs text-slate-500">
                            Conseil : 3–5 lignes max, puis détail dans le PDF si besoin.
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Fichiers (multi)</label>

                        <div class="rounded-[22px] border border-dashed border-black/20 bg-slate-50 p-5">
                            <input
                                type="file"
                                name="files[]"
                                multiple
                                class="block w-full text-sm text-slate-700
                                       file:mr-4 file:rounded-xl file:border-0
                                       file:bg-slate-900 file:px-4 file:py-2
                                       file:text-sm file:font-semibold file:text-white
                                       hover:file:bg-black"
                            />
                            <div class="mt-2 text-xs text-slate-500">
                                PDF, images, docs… <span class="font-semibold text-slate-700">10MB max</span> par fichier.
                            </div>
                        </div>

                        @error('files.*')
                            <div class="text-sm text-rose-600 mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="pt-2 flex flex-col sm:flex-row gap-2">
                        <button class="rounded-2xl bg-slate-900 px-6 py-2.5 text-sm font-semibold text-white hover:bg-black">
                            Publier
                        </button>

                        @if(\Illuminate\Support\Facades\Route::has('teacher.courses.index'))
                            <a href="{{ route('teacher.courses.index') }}"
                               class="rounded-2xl border border-black/10 bg-white px-6 py-2.5 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                                Annuler
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            {{-- Tips --}}
            <div class="rounded-[28px] border border-black/10 bg-white/70 backdrop-blur-xl p-6 shadow-sm">
                <div class="text-sm font-semibold text-slate-900">Bonnes pratiques</div>
                <div class="mt-1 text-xs text-slate-500">Pour gagner du temps et aider les parents.</div>

                <div class="mt-4 space-y-3 text-sm text-slate-700">
                    <div class="rounded-2xl border border-black/5 bg-white/60 p-4">
                        <div class="font-semibold text-slate-900">1) Titre précis</div>
                        <div class="mt-1 text-xs text-slate-500">Chapitre + thème + classe.</div>
                    </div>

                    <div class="rounded-2xl border border-black/5 bg-white/60 p-4">
                        <div class="font-semibold text-slate-900">2) Description courte</div>
                        <div class="mt-1 text-xs text-slate-500">Objectif + consigne principale.</div>
                    </div>

                    <div class="rounded-2xl border border-black/5 bg-white/60 p-4">
                        <div class="font-semibold text-slate-900">3) Fichiers bien nommés</div>
                        <div class="mt-1 text-xs text-slate-500">Ex : “Maths_Fractions_2AC.pdf”.</div>
                    </div>
                </div>

                <div class="mt-4 text-xs text-slate-500">
                    Les parents voient automatiquement les cours publiés pour la classe.
                </div>
            </div>
        </div>
    </div>
</x-teacher-layout>
