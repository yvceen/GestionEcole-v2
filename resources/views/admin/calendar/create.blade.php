<x-admin-layout title="Ajouter un evenement calendrier" subtitle="Ajoutez une date importante pour l ensemble de l ecole.">
    <x-ui.page-header
        title="Nouvel evenement calendrier"
        subtitle="Examens, vacances, reunions ou autres activites datees."
    />

    @include('partials.calendar.form', [
        'action' => route('admin.calendar.store'),
        'method' => 'POST',
        'submitLabel' => 'Enregistrer l evenement',
    ])
</x-admin-layout>
