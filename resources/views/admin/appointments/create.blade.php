<x-admin-layout title="Nouveau rendez-vous">
    <x-ui.page-header
        title="Nouveau rendez-vous"
        subtitle="Creez une demande administrative ou ajoutez directement un rendez-vous dans le flux existant."
    >
        <x-slot name="actions">
            <x-ui.button :href="route('admin.appointments.index')" variant="secondary">Retour</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <form method="POST" action="{{ route('admin.appointments.store') }}" class="space-y-6">
        @csrf
        @include('admin.appointments.partials.form', ['appointment' => null])
    </form>
</x-admin-layout>
