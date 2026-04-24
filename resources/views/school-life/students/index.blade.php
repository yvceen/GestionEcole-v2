<x-school-life-layout title="Dossiers eleves" subtitle="Contacts parents, classes, absences, retards et indicateurs utiles au quotidien.">
    <x-ui.card title="Recherche" subtitle="Filtrez par classe ou recherchez un eleve / parent / telephone.">
        <form method="GET" class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_220px_auto_auto]">
            <input name="q" value="{{ $q }}" class="app-input" placeholder="Nom eleve, parent, telephone...">
            <select name="classroom_id" class="app-input">
                <option value="">Toutes les classes</option>
                @foreach($classrooms as $classroom)
                    <option value="{{ $classroom->id }}" @selected((int) $classroomId === (int) $classroom->id)>{{ $classroom->name }}</option>
                @endforeach
            </select>
            <x-ui.button type="submit" variant="primary">Filtrer</x-ui.button>
            <x-ui.button :href="route('school-life.students.index')" variant="secondary">Reinitialiser</x-ui.button>
        </form>
    </x-ui.card>

    <x-ui.card title="Eleves" subtitle="Liste operationnelle avec contacts et indicateurs.">
        <div class="overflow-x-auto rounded-2xl border border-slate-200">
            <table class="app-table min-w-[860px]">
                <thead>
                    <tr>
                        <th>Eleve</th>
                        <th>Classe</th>
                        <th>Parent</th>
                        <th>Contact</th>
                        <th>Absences</th>
                        <th>Retards</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                        <tr>
                            <td class="font-semibold text-slate-950">{{ $student->full_name }}</td>
                            <td>{{ $student->classroom?->name ?? '-' }}</td>
                            <td>{{ $student->parentUser?->name ?? '-' }}</td>
                            <td>
                                <div>{{ $student->parentUser?->phone ?? '-' }}</div>
                                <div class="text-xs text-slate-500">{{ $student->parentUser?->email ?? '' }}</div>
                            </td>
                            <td><x-ui.badge variant="danger">{{ (int) $student->absences_count }}</x-ui.badge></td>
                            <td><x-ui.badge variant="warning">{{ (int) $student->late_count }}</x-ui.badge></td>
                            <td><x-ui.badge variant="info">{{ (int) $student->grades_count }}</x-ui.badge></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-10 text-center text-sm text-slate-500">Aucun eleve trouve.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-5">{{ $students->links() }}</div>
    </x-ui.card>
</x-school-life-layout>
