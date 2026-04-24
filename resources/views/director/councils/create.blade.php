<x-director-layout title="Nouvelle décision – Conseil de classe">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Nouvelle décision</h1>
            <p class="mt-1 text-sm text-slate-500">Ajoutez une décision de conseil de classe.</p>
        </div>
        <a href="{{ route('director.councils.index') }}" class="text-sm font-semibold text-slate-700 hover:underline">← Retour</a>
    </div>

    @if($errors->any())
        <div class="mt-6 rounded-3xl border border-red-200 bg-red-50 p-4 text-red-900">
            <ul class="list-disc ml-5">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('director.councils.store') }}"
          class="mt-8 rounded-[28px] border border-black/10 bg-white/80 p-6 shadow-sm space-y-5">
        @csrf

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="md:col-span-2">
                <label class="block text-sm font-semibold mb-1">Classe</label>
                <select name="classroom_id" class="w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5" required>
                    <option value="">Choisir…</option>
                    @foreach(($classrooms ?? []) as $c)
                        <option value="{{ $c->id }}" @selected(old('classroom_id')==(string)$c->id)>
                            {{ $c->name ?? ('Classe #' . $c->id) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1">Date</label>
                <input type="date" name="held_at" value="{{ old('held_at', now()->toDateString()) }}"
                       class="w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5" required />
            </div>
        </div>

        <div>
            <label class="block text-sm font-semibold mb-1">Période</label>
            <input name="period" value="{{ old('period') }}"
                   class="w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5"
                   placeholder="Ex: 1er trimestre 2025/2026" required />
        </div>

        <div>
            <label class="block text-sm font-semibold mb-1">Décision / Recommandation</label>
            <textarea name="decision" rows="5"
                      class="w-full rounded-2xl border border-black/10 bg-white px-4 py-3"
                      placeholder="Écrivez ici les décisions (soutien, orientation, suivi…)" required>{{ old('decision') }}</textarea>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('director.councils.index') }}"
               class="rounded-2xl border border-black/10 bg-white px-5 py-2.5 text-sm font-semibold hover:bg-slate-50">
                Annuler
            </a>
            <button class="rounded-2xl bg-black px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-900">
                Enregistrer
            </button>
        </div>
    </form>
</x-director-layout>
