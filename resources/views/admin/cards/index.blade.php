<x-admin-layout title="Cartes eleves et parents" subtitle="Generez des cartes propres a l ecole, utilisables pour impression et pour les scans QR de la vie scolaire.">
    <x-ui.page-header
        title="Cartes numeriques"
        subtitle="Les cartes eleves et parents utilisent un code securise. L administration peut regenerer un QR code si une carte doit etre remplacee."
    />

    @include('partials.cards.manage-index', [
        'showStudentRoute' => 'admin.cards.students.show',
        'showParentRoute' => 'admin.cards.parents.show',
    ])
</x-admin-layout>
