<x-director-layout title="Presence et retards" subtitle="Analysez les absences et retards par classe et par eleve, avec une vue direction lisible et exploitable.">
    <x-ui.page-header
        title="Monitoring des presences"
        subtitle="Vue de suivi pour la direction, limitee a l'ecole active et orientee pilotage."
    >
        <x-slot name="actions">
            <x-ui.button :href="route('director.monitoring')" variant="secondary">
                Retour au suivi global
            </x-ui.button>
        </x-slot>
    </x-ui.page-header>

    @include('partials.attendance.monitoring')
</x-director-layout>
