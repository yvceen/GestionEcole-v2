<x-school-life-layout title="Modifier utilisateur" subtitle="Mise a jour du compte et des informations de contact.">
    @include('school-life.users.partials.form', [
        'action' => route('school-life.users.update', $user),
        'method' => 'PUT',
        'submitLabel' => 'Enregistrer',
        'user' => $user,
        'linkedStudent' => $linkedStudent,
    ])
</x-school-life-layout>
