<x-admin-layout title="Structure scolaire">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Structure scolaire</h1>
            <p class="mt-1 text-sm text-slate-500">
                Ajouter des niveaux et des classes بسهولة، ومن هنا غادي يتعمّر dropdown ديال classes فـ élèves.
            </p>
        </div>
    </div>

    @if(session('success'))
        <div class="mt-6 rounded-3xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-900">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mt-6 rounded-3xl border border-red-200 bg-red-50 p-4 text-red-900">
            <ul class="list-disc ml-5">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- LEFT: Levels --}}
        <div class="rounded-[28px] border border-black/5 bg-white p-6 shadow-[0_18px_45px_-30px_rgba(0,0,0,.35)]">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900">Niveaux</h2>
            </div>

            <form method="POST" action="{{ route('admin.structure.levels.store') }}" class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-3">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Code</label>
                    <input name="code" value="{{ old('code') }}"
                           class="w-full rounded-2xl border-slate-200/70 bg-white/70 focus:border-slate-900 focus:ring-slate-900"
                           placeholder="MAT / PRI / COL / LYC" required>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Nom</label>
                    <input name="name" value="{{ old('name') }}"
                           class="w-full rounded-2xl border-slate-200/70 bg-white/70 focus:border-slate-900 focus:ring-slate-900"
                           placeholder="Maternelle / Primaire ..." required>
                </div>

                <div class="md:col-span-3 flex justify-end">
                    <button class="rounded-2xl bg-black px-5 py-2 text-sm font-semibold text-white hover:bg-slate-900">
                        + Ajouter niveau
                    </button>
                </div>
            </form>

            <div class="mt-6 space-y-2">
                @forelse($levels as $lvl)
                    <div class="rounded-2xl border border-slate-200/70 bg-slate-50 px-4 py-3 flex items-center justify-between">
                        <div>
                            <div class="text-sm font-semibold text-slate-900">{{ $lvl->name }}</div>
                            <div class="text-xs text-slate-500">Code: {{ $lvl->code }} • Classes: {{ $lvl->classrooms->count() }}</div>
                        </div>
                        <div class="text-xs text-slate-500">Ordre: {{ $lvl->sort_order }}</div>
                    </div>
                @empty
                    <div class="text-sm text-slate-500">Aucun niveau. Ajoute un niveau pour commencer.</div>
                @endforelse
            </div>
        </div>

        {{-- RIGHT: Classrooms --}}
        <div class="rounded-[28px] border border-black/5 bg-white p-6 shadow-[0_18px_45px_-30px_rgba(0,0,0,.35)]">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900">Classes</h2>
            </div>

            <form method="POST" action="{{ route('admin.structure.classrooms.store') }}" class="mt-4 space-y-3">
                @csrf

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Niveau</label>
                    <select name="level_id"
                            class="w-full rounded-2xl border-slate-200/70 bg-white/70 focus:border-slate-900 focus:ring-slate-900"
                            required>
                        <option value="">— Choisir niveau —</option>
                        @foreach($levels as $lvl)
                            <option value="{{ $lvl->id }}" @selected(old('level_id') == $lvl->id)>
                                {{ $lvl->name }} ({{ $lvl->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Nom de la classe</label>
                        <input name="name" value="{{ old('name') }}"
                               class="w-full rounded-2xl border-slate-200/70 bg-white/70 focus:border-slate-900 focus:ring-slate-900"
                               placeholder="CP A / CE1 B / 1AC C ..." required>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Section (optionnel)</label>
                        <input name="section" value="{{ old('section') }}"
                               class="w-full rounded-2xl border-slate-200/70 bg-white/70 focus:border-slate-900 focus:ring-slate-900"
                               placeholder="CP-A / CE1-B ... (sinon auto)">
                    </div>
                </div>

                <div class="flex justify-end">
                    <button class="rounded-2xl bg-black px-5 py-2 text-sm font-semibold text-white hover:bg-slate-900">
                        + Ajouter classe
                    </button>
                </div>
            </form>

            <div class="mt-6 space-y-5">
                @forelse($levels as $lvl)
                    <div class="rounded-3xl border border-slate-200/70 bg-white p-4">
                        <div class="flex items-center justify-between">
                            <div class="text-sm font-semibold text-slate-900">{{ $lvl->name }} <span class="text-xs text-slate-500">({{ $lvl->code }})</span></div>
                            <div class="text-xs text-slate-500">{{ $lvl->classrooms->count() }} classes</div>
                        </div>

                        <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-2">
                            @forelse($lvl->classrooms as $c)
                                <a href="{{ route('admin.structure.classrooms.show', $c) }}"
                                   class="rounded-2xl border border-slate-200/70 bg-slate-50 px-3 py-2 hover:bg-white transition">
                                    <div class="text-sm font-semibold text-slate-900">{{ $c->name }}</div>
                                    <div class="text-xs text-slate-500">Section: {{ $c->section }} • Ordre: {{ $c->sort_order }}</div>
                                </a>
                            @empty
                                <div class="text-sm text-slate-500">Aucune classe.</div>
                            @endforelse
                        </div>
                    </div>
                @empty
                    <div class="text-sm text-slate-500">Ajoute un niveau d’abord.</div>
                @endforelse
            </div>
        </div>
    </div>
</x-admin-layout>
