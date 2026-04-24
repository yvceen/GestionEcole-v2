<x-admin-layout title="Ajouter un bloc agenda" subtitle="Ajoutez un cours, un examen ou une activite dans l agenda hebdomadaire.">
    <x-ui.page-header
        title="Nouvel evenement"
        subtitle="Les blocs apparaissent ensuite dans la vue semaine FullCalendar."
    />

    @include('partials.events.form', [
        'action' => route('admin.events.store'),
        'method' => 'POST',
        'submitLabel' => 'Enregistrer le bloc',
    ])
</x-admin-layout>
