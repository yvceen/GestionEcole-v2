<x-admin-layout title="Cartes Élèves et parents" subtitle="Générez des cartes propres a l'École, utilisables pour impression et pour les scans QR de la vie scolaire.">
    <x-ui.page-header
        title="Cartes numeriques"
        subtitle="Les cartes Élèves et parents utilisent un code sécurisé. L administration peut regenerer un QR code si une carte doit etre remplacee."
    />

    @include('partials.cards.manage-index', [
        'showStudentRoute' => 'admin.cards.students.show',
        'showParentRoute' => 'admin.cards.parents.show',
    ])
</x-admin-layout>
