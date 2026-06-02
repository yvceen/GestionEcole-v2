<x-admin-layout title="Calendrier scolaire" subtitle="Pilotez les examens, vacances et Événements communiques a toute l'École active.">
    <x-ui.page-header
        title="Calendrier scolaire"
        subtitle="Les Événements saisis ici sont visibles dans les portails concernes en lecture seule."
    >
        <x-slot name="actions">
            <x-ui.button :href="route('admin.calendar.create')" variant="primary">
                Ajouter un Événement
            </x-ui.button>
        </x-slot>
    </x-ui.page-header>

    @include('partials.calendar.list', ['showManageActions' => true])
</x-admin-layout>
