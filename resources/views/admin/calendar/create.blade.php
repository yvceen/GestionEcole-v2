<x-admin-layout title="Ajouter un Événement calendrier" subtitle="Ajoutez une date importante pour l ensemble de l'École.">
    <x-ui.page-header
        title="Nouvel Événement calendrier"
        subtitle="Examens, vacances, reunions ou autres activités datees."
    />

    @include('partials.calendar.form', [
        'action' => route('admin.calendar.store'),
        'method' => 'POST',
        'submitLabel' => 'Enregistrer l Événement',
    ])
</x-admin-layout>
