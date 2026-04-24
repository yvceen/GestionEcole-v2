<x-school-life-layout title="Notes" subtitle="Vue de consultation des notes pour le suivi vie scolaire.">
    <x-ui.card title="Filtres" subtitle="Recherche simple par eleve et classe.">
        <form method="GET" class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_220px_auto_auto]">
            <input name="q" value="{{ $q }}" class="app-input" placeholder="Nom de l'eleve">
            <select name="classroom_id" class="app-input">
                <option value="">Toutes les classes</option>
                @foreach($classrooms as $classroom)
                    <option value="{{ $classroom->id }}" @selected((int) $classroomId === (int) $classroom->id)>{{ $classroom->name }}</option>
                @endforeach
            </select>
            <x-ui.button type="submit" variant="primary">Filtrer</x-ui.button>
            <x-ui.button :href="route('school-life.grades.index')" variant="secondary">Reinitialiser</x-ui.button>
        </form>
    </x-ui.card>

    <x-ui.card title="Dernieres notes" subtitle="Consultation uniquement.">
        <div class="overflow-x-auto rounded-2xl border border-slate-200">
            <table class="app-table min-w-[820px]">
                <thead>
                    <tr>
                        <th>Eleve</th>
                        <th>Classe</th>
                        <th>Matiere</th>
                        <th>Note</th>
                        <th>Enseignant</th>
                        <th>Commentaire</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($grades as $grade)
                        <tr>
                            <td class="font-semibold text-slate-950">{{ $grade->student?->full_name ?? '-' }}</td>
                            <td>{{ $grade->classroom?->name ?? $grade->student?->classroom?->name ?? '-' }}</td>
                            <td>{{ $grade->subject?->name ?? '-' }}</td>
                            <td class="font-semibold text-slate-950">{{ $grade->score }}/{{ $grade->max_score }}</td>
                            <td>{{ $grade->teacher?->name ?? '-' }}</td>
                            <td>{{ $grade->comment ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-sm text-slate-500">Aucune note trouvee.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-5">{{ $grades->links() }}</div>
    </x-ui.card>
</x-school-life-layout>
