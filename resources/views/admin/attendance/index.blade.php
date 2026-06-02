<x-admin-layout title="Présence et retards" subtitle="Supervisez les appels de classe, les absences et les retards de tout l'Établissement avec une lecture claire par classe et par Élève.">
    <x-ui.page-header
        title="Suivi des présences"
        subtitle="Page de monitoring pour l'administration. Les données restent strictement limitees a l'École active."
    >
        <x-slot name="actions">
            <x-ui.button :href="route('admin.students.index')" variant="secondary">
                Voir les Élèves
            </x-ui.button>
        </x-slot>
    </x-ui.page-header>

    @include('partials.attendance.monitoring')
</x-admin-layout>
