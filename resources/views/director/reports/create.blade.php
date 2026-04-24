<x-director-layout title="Nouveau rapport pédagogique">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Nouveau rapport</h1>
            <p class="mt-1 text-sm text-slate-500">Rédigez un rapport mensuel clair et exploitable.</p>
        </div>
        <a href="{{ route('director.reports.index') }}" class="text-sm font-semibold text-slate-700 hover:underline">← Retour</a>
    </div>

    @if($errors->any())
        <div class="mt-6 rounded-3xl border border-red-200 bg-red-50 p-4 text-red-900">
            <ul class="list-disc ml-5">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('director.reports.store') }}"
          class="mt-8 rounded-[28px] border border-black/10 bg-white/80 p-6 shadow-sm space-y-5">
        @csrf

        <div>
            <label class="block text-sm font-semibold mb-1">Mois</label>
            <input name="month" value="{{ old('month') }}"
                   class="w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5"
                   placeholder="Ex: Janvier 2026" required />
        </div>

        <div>
            <label class="block text-sm font-semibold mb-1">Résumé</label>
            <textarea name="summary" rows="4"
                      class="w-full rounded-2xl border border-black/10 bg-white px-4 py-3"
                      placeholder="Synthèse globale : évolution, points positifs, difficultés…" required>{{ old('summary') }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-semibold mb-1">Recommandations</label>
            <textarea name="recommendations" rows="5"
                      class="w-full rounded-2xl border border-black/10 bg-white px-4 py-3"
                      placeholder="Actions proposées : soutien, réunions, suivi, planification…">{{ old('recommendations') }}</textarea>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('director.reports.index') }}"
               class="rounded-2xl border border-black/10 bg-white px-5 py-2.5 text-sm font-semibold hover:bg-slate-50">
                Annuler
            </a>
            <button class="rounded-2xl bg-black px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-900">
                Enregistrer
            </button>
        </div>
    </form>
</x-director-layout>
