<x-parent-layout
    title="Emploi du temps"
    subtitle="Consultez le planning hebdomadaire de votre enfant dans une grille claire et facile a imprimer."
>
    <section class="student-panel mb-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="student-panel-title">{{ $student->full_name }}</p>
                <p class="student-panel-copy">
                    {{ $student->classroom?->name ?? '-' }}
                    @if($student->classroom?->level?->name)
                        <span class="mx-2 text-slate-300">|</span>{{ $student->classroom->level->name }}
                    @endif
                </p>
            </div>
            <button type="button" onclick="window.print()" class="app-button-secondary">Imprimer</button>
        </div>
    </section>

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
</x-parent-layout>
