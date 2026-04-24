<x-school-life-layout title="Cartes et QR" subtitle="Consultez rapidement les cartes eleves et parents pour les operations de terrain et les controles du jour.">
    <x-ui.page-header
        title="Cartes operationnelles"
        subtitle="La vie scolaire peut consulter et imprimer les cartes sans regenerer les QR codes."
    >
        <x-slot name="actions">
            <x-ui.button :href="route('attendance.scan.page')" variant="primary">
                Ouvrir le scan QR
            </x-ui.button>
        </x-slot>
    </x-ui.page-header>

    @include('partials.cards.manage-index', [
        'showStudentRoute' => 'school-life.cards.students.show',
        'showParentRoute' => 'school-life.cards.parents.show',
    ])
</x-school-life-layout>
