<x-school-life-layout title="Presences et retards" subtitle="Suivez les absences et retards de l'ecole active, puis passez au scan ou a la saisie manuelle si une correction est necessaire.">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row">
        <x-ui.button :href="route('school-life.qr-scan.index')" variant="primary">
            Scan
        </x-ui.button>
        <x-ui.button :href="route('school-life.attendance.manual')" variant="secondary">
            Saisie manuelle
        </x-ui.button>
    </div>
    @include('partials.attendance.monitoring')
</x-school-life-layout>
