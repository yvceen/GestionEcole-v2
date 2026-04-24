<x-admin-layout title="Nouvel emploi du temps">
    <x-ui.page-header
        title="Nouveau creneau"
        subtitle="Ajoutez un creneau avec un formulaire plus aere et des actions bien alignees."
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

    <x-ui.card title="Creneau" subtitle="Renseignez la classe, le jour, les horaires et l'enseignant.">
        <form method="POST" action="{{ route('admin.timetable.store') }}" class="space-y-6">
            @csrf
            @include('admin.timetable._form')

            <div class="flex items-center gap-3 border-t border-slate-200 pt-4">
                <x-ui.button type="submit" variant="primary">Enregistrer</x-ui.button>
                <x-ui.button :href="route('admin.timetable.index', ['classroom_id' => old('classroom_id', $selectedClassroomId)])" variant="secondary">Annuler</x-ui.button>
            </div>
        </form>
    </x-ui.card>
</x-admin-layout>
