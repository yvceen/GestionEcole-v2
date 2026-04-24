<x-admin-layout title="Classes">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Modifier classe</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $classroom->name }}</p>
        </div>
        <a href="{{ route('admin.classrooms.show', $classroom) }}" class="text-sm font-semibold text-slate-700 hover:underline">← Retour</a>
    </div>

    @if($errors->any())
        <div class="mt-6 rounded-3xl border border-red-200 bg-red-50 p-4 text-red-900">
            <ul class="list-disc ml-5">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.classrooms.update', $classroom) }}"
          class="mt-8 rounded-[28px] border border-white/60 bg-white/70 backdrop-blur-2xl p-6 space-y-5">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold mb-1">Niveau</label>
                <select name="level_id" class="w-full rounded-2xl border-slate-200/70 bg-white/70">
                    <option value="">— (optionnel) —</option>
                    @foreach($levels as $lvl)
                        <option value="{{ $lvl->id }}" @selected(old('level_id', $classroom->level_id) == $lvl->id)>
                            {{ $lvl->name }} {{ $lvl->code ? "({$lvl->code})" : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1">Section</label>
                <input name="section" class="w-full rounded-2xl border-slate-200/70 bg-white/70"
                       value="{{ old('section', $classroom->section) }}" placeholder="Ex: A / B / C">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold mb-1">Nom</label>
                <input name="name" class="w-full rounded-2xl border-slate-200/70 bg-white/70"
                       value="{{ old('name', $classroom->name) }}" required>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1">Ordre</label>
                <input name="sort_order" type="number"
                       class="w-full rounded-2xl border-slate-200/70 bg-white/70"
                       value="{{ old('sort_order', $classroom->sort_order ?? 0) }}">
            </div>
        </div>

        <div class="flex items-center gap-2">
            <input id="is_active" type="checkbox" name="is_active" value="1"
                   class="rounded border-slate-300"
                   {{ old('is_active', $classroom->is_active) ? 'checked' : '' }}>
            <label for="is_active" class="text-sm font-semibold">Actif</label>
        </div>

        <div class="pt-2 flex items-center justify-end gap-3">
            <a href="{{ route('admin.classrooms.show', $classroom) }}"
               class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold hover:bg-slate-50">
                Annuler
            </a>
            <button class="rounded-2xl bg-black px-5 py-2 text-sm font-semibold text-white">
                Enregistrer
            </button>
        </div>
    </form>
</x-admin-layout>
