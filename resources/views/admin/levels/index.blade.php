<x-admin-layout title="Niveaux">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Niveaux</h1>
            <p class="mt-1 text-sm text-slate-500">Gérez Maternel / Primaire / Collège / Lycée…</p>
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

    <div class="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="rounded-[28px] border border-black/5 bg-white p-6">
            <h2 class="text-lg font-semibold text-slate-900">Ajouter un niveau</h2>

            <form method="POST" action="{{ route('admin.levels.store') }}" class="mt-4 space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-semibold mb-1">Nom</label>
                    <input name="name" class="w-full rounded-2xl border-slate-200/70 bg-white/70"
                           placeholder="Ex: Primaire" required>
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-1">Code (optionnel)</label>
                    <input name="code" class="w-full rounded-2xl border-slate-200/70 bg-white/70"
                           placeholder="Ex: PRI">
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-semibold mb-1">Ordre</label>
                        <input name="sort_order" type="number" class="w-full rounded-2xl border-slate-200/70 bg-white/70" value="0">
                    </div>
                    <div class="flex items-center gap-2 pt-7">
                        <input id="is_active" type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300">
                        <label for="is_active" class="text-sm font-semibold">Actif</label>
                    </div>
                </div>

                <button class="rounded-2xl bg-black px-5 py-2 text-sm font-semibold text-white">
                    Ajouter
                </button>
            </form>
        </div>

        <div class="lg:col-span-2 rounded-[28px] border border-black/5 bg-white overflow-hidden">
            <div class="px-6 py-4 border-b border-black/5">
                <div class="text-sm font-semibold text-slate-900">Liste des niveaux</div>
            </div>

            <div class="p-6">
                @if($levels->isEmpty())
                    <div class="text-sm text-slate-500">Aucun niveau pour le moment.</div>
                @else
                    <div class="space-y-3">
                        @foreach($levels as $lvl)
                            <div class="rounded-2xl border border-black/5 bg-white/70 px-4 py-3 flex items-center justify-between">
                                <div>
                                    <div class="font-semibold text-slate-900">
                                        {{ $lvl->name }}
                                        @if($lvl->code)
                                            <span class="text-xs text-slate-500">({{ $lvl->code }})</span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        Ordre: {{ $lvl->sort_order }} • {{ $lvl->is_active ? 'Actif' : 'Inactif' }}
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.levels.edit', $lvl) }}"
                                       class="rounded-2xl border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold hover:bg-slate-50">
                                        Modifier
                                    </a>

                                    <form method="POST" action="{{ route('admin.levels.destroy', $lvl) }}"
                                          onsubmit="return confirm('Supprimer ce niveau ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="rounded-2xl border border-red-200 bg-red-50 px-3 py-1.5 text-sm font-semibold text-red-700 hover:bg-red-100">
                                            Supprimer
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-admin-layout>
