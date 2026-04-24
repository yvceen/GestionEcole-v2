<x-admin-layout title="Modifier le rendez-vous">
    <x-ui.page-header
        title="Modifier le rendez-vous"
        subtitle="Mettez a jour la date, le statut, l enfant concerne et les notes internes depuis une seule fiche."
    >
        <x-slot name="actions">
            <x-ui.button :href="route('admin.appointments.show', $appointment)" variant="secondary">Voir le detail</x-ui.button>
            <x-ui.button :href="route('admin.appointments.index')" variant="ghost">Retour</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <form method="POST" action="{{ route('admin.appointments.update', $appointment) }}" class="space-y-6">
        @csrf
        @method('PUT')
        @include('admin.appointments.partials.form', ['appointment' => $appointment])
    </form>
</x-admin-layout>
