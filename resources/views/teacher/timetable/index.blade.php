<x-teacher-layout title="Emploi du temps">
    <section class="app-card mb-6 p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm font-semibold text-slate-900">Lecture de la semaine</p>
                <p class="mt-1 text-xs text-slate-500">Selectionnez une classe pour afficher une grille plus lisible.</p>
            </div>
            <button type="button" onclick="window.print()" class="app-button-secondary">Imprimer</button>
        </div>
    </section>

    <section class="app-card mb-6 p-5">
        <form method="GET" action="{{ route('teacher.timetable.index') }}" class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
            <div>
                <label for="classroom_id" class="mb-2 block text-sm font-semibold text-slate-700">Classe</label>
                <select id="classroom_id" name="classroom_id" class="app-input">
                    @foreach($classrooms as $classroom)
                        <option value="{{ $classroom->id }}" @selected((int) $selectedClassroomId === (int) $classroom->id)>
                            {{ $classroom->name }} @if($classroom->level) ({{ $classroom->level->name }}) @endif
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="app-button-secondary">Voir</button>
        </form>
    </section>

    @if($classrooms->isEmpty())
        <section class="app-card p-6 text-sm text-slate-600">
            Aucune classe ne vous est affectee pour le moment.
        </section>
    @else
        @include('partials.timetable-grid', [
            'settings' => $settings,
            'slots' => $slots,
            'selectedClass' => $selectedClass,
            'days' => $days,
            'times' => $times,
            'slotsByDay' => $slotsByDay,
            'lunchBlock' => $lunchBlock,
            'editable' => false,
        ])
    @endif
</x-teacher-layout>
