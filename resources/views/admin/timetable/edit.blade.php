<x-admin-layout title="Modifier l'emploi du temps">
    <x-ui.page-header
        title="Modifier un creneau"
        subtitle="Ajustez un creneau existant en gardant la meme structure que la creation."
    >
        <x-slot name="actions">
            <x-ui.button :href="route('admin.timetable.settings.edit')" variant="secondary">
                Parametres planning
            </x-ui.button>
        </x-slot>
    </x-ui.page-header>

    @if($errors->any())
        <x-ui.alert variant="error">
            <ul class="space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-ui.alert>
    @endif

    <x-ui.card title="Creneau" subtitle="Mettez a jour les horaires, la matiere, l'enseignant et la salle.">
        <form method="POST" action="{{ route('admin.timetable.update', $timetable) }}" class="space-y-6">
            @csrf
            @method('PUT')
            @include('admin.timetable._form', ['timetable' => $timetable])

            <div class="flex items-center gap-3 border-t border-slate-200 pt-4">
                <x-ui.button type="submit" variant="primary">Mettre a jour</x-ui.button>
                <x-ui.button :href="route('admin.timetable.index', ['classroom_id' => $timetable->classroom_id])" variant="secondary">Retour</x-ui.button>
            </div>
        </form>
    </x-ui.card>
</x-admin-layout>
