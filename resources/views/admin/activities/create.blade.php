<x-admin-layout title="Nouvelle activite" subtitle="Ajout d une activite scolaire dans le calendrier operationnel.">
    @include('partials.activities.form', [
        'action' => route('admin.activities.store'),
        'submitLabel' => 'Creer activite',
    ])
</x-admin-layout>
