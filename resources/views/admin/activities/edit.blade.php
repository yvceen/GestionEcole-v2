<x-admin-layout title="Modifier activite" subtitle="Mise a jour des informations de l activite.">
    @include('partials.activities.form', [
        'action' => route('admin.activities.update', $activity),
        'method' => 'PUT',
        'submitLabel' => 'Mettre a jour',
    ])
</x-admin-layout>
