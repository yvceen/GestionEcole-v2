<x-school-life-layout title="Nouvel utilisateur" subtitle="Création de comptes operationnels sans role admin.">
    @include('school-life.users.partials.form', [
        'action' => route('school-life.users.store'),
        'method' => 'POST',
        'submitLabel' => 'Creer',
        'user' => null,
        'linkedStudent' => null,
    ])
</x-school-life-layout>
