<x-admin-layout title="Modifier un evenement calendrier" subtitle="Mettez a jour une date ou une information sans changer la logique applicative.">
    <x-ui.page-header
        title="Modifier l evenement"
        subtitle="Ajustez le titre, la periode ou la description."
    />

    @include('partials.calendar.form', [
        'action' => route('admin.calendar.update', $event),
        'method' => 'PUT',
        'submitLabel' => 'Mettre a jour',
    ])
</x-admin-layout>
