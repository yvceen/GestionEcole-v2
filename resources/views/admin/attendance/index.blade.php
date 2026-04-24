<x-admin-layout title="Presence et retards" subtitle="Supervisez les appels de classe, les absences et les retards de tout l'etablissement avec une lecture claire par classe et par eleve.">
    <x-ui.page-header
        title="Suivi des presences"
        subtitle="Page de monitoring pour l'administration. Les donnees restent strictement limitees a l'ecole active."
    >
        <x-slot name="actions">
            <x-ui.button :href="route('admin.students.index')" variant="secondary">
                Voir les eleves
            </x-ui.button>
        </x-slot>
    </x-ui.page-header>

    @include('partials.attendance.monitoring')
</x-admin-layout>
