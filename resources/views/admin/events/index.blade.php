<x-admin-layout title="Agenda hebdomadaire" subtitle="Vue semaine moderne avec blocs colores pour les cours, examens et activites.">
    <x-ui.page-header
        title="Agenda moderne"
        subtitle="Agenda hebdomadaire type dashboard, avec filtres classe et enseignant et gestion admin des blocs."
    />

    @include('partials.events.week-calendar')
</x-admin-layout>
