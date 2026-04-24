<x-admin-layout title="Modifier un bloc agenda" subtitle="Mettez a jour un bloc hebdomadaire sans modifier la logique des autres modules.">
    <x-ui.page-header
        title="Modifier l evenement"
        subtitle="Ajustez l horaire, la classe, l enseignant ou la couleur."
    />

    @include('partials.events.form', [
        'action' => route('admin.events.update', $event),
        'method' => 'PUT',
        'submitLabel' => 'Mettre a jour',
    ])
</x-admin-layout>
