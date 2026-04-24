<x-admin-layout title="Nouveau devoir">
    @php
        $routePrefix = $routePrefix ?? 'admin.homeworks';
    @endphp

    <x-ui.page-header
        title="Creer un devoir"
        subtitle="Ajoutez un devoir avec une presentation simple, lisible et alignee avec le reste de l interface."
    />

    <x-ui.card title="Informations du devoir" subtitle="Selectionnez la classe, puis renseignez le contenu et l echeance.">
        <form method="POST" action="{{ route($routePrefix . '.store') }}" class="space-y-5">
            @csrf

            <x-ui.select label="Classe" name="classroom_id" required>
                <option value="">Choisir une classe</option>
                @foreach($classrooms as $classroom)
                    <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                @endforeach
            </x-ui.select>

            <x-ui.select label="Matiere" name="subject_id">
                <option value="">Choisir une matiere</option>
                @foreach($subjects as $subject)
                    <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                @endforeach
            </x-ui.select>

            <x-ui.input label="Titre" name="title" required />
            <x-ui.textarea label="Description" name="description" rows="6"></x-ui.textarea>
            <x-ui.input label="Date limite" name="due_at" type="datetime-local" />

            <div class="flex justify-end gap-3">
                <x-ui.button :href="route($routePrefix . '.index')" variant="secondary">
                    Annuler
                </x-ui.button>
                <x-ui.button type="submit" variant="primary">
                    Enregistrer
                </x-ui.button>
            </div>
        </form>
    </x-ui.card>
</x-admin-layout>
