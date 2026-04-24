<x-student-layout title="Emploi du temps" subtitle="Planifiez votre semaine sur une grille claire, avec impression rapide pour une consultation hors ligne.">
    <section class="student-panel mb-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="student-panel-title">Planning de la semaine</p>
                <p class="student-panel-copy">Consultez rapidement vos horaires, enseignants et salles sur une grille claire.</p>
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
</x-student-layout>
