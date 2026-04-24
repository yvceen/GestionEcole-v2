<x-admin-layout title="Nouveau cours">
    <x-ui.page-header
        title="Créer un cours"
        subtitle="Ajoutez un nouveau contenu pédagogique avec une structure de formulaire cohérente."
    />

    <x-ui.card title="Informations du cours" subtitle="Renseignez la classe, le titre et la description.">
        <form method="POST" action="{{ route('admin.courses.store') }}" class="space-y-5">
            @csrf

            <x-ui.select label="Classe" name="classroom_id" required>
                <option value="">Choisir une classe</option>
                @foreach($classrooms as $classroom)
                    <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                @endforeach
            </x-ui.select>

            <x-ui.input label="Titre" name="title" required />

            <x-ui.textarea label="Description" name="description" rows="6"></x-ui.textarea>

            <div class="flex justify-end gap-3">
                <x-ui.button :href="route('admin.courses.index')" variant="secondary">
                    Annuler
                </x-ui.button>
                <x-ui.button type="submit" variant="primary">
                    Enregistrer
                </x-ui.button>
            </div>
        </form>
    </x-ui.card>
</x-admin-layout>
