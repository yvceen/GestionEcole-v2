@php
    $routePrefix = $routePrefix ?? 'admin.homeworks';
    $layoutComponent = $layoutComponent ?? 'admin-layout';
@endphp

<x-dynamic-component :component="$layoutComponent" title="Modifier le devoir">

    <x-ui.page-header
        title="Modifier le devoir"
        subtitle="Mettez a jour le contenu, la classe cible ou l echeance sans casser le suivi de publication."
    />

    <x-ui.card title="Edition du devoir" subtitle="Les modifications sont appliquees au devoir deja publie ou en attente.">
        <form method="POST" action="{{ route($routePrefix . '.update', $homework) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <x-ui.select label="Classe" name="classroom_id" required>
                <option value="">Choisir une classe</option>
                @foreach($classrooms as $classroom)
                    <option value="{{ $classroom->id }}" @selected((int) old('classroom_id', $homework->classroom_id) === (int) $classroom->id)>{{ $classroom->name }}</option>
                @endforeach
            </x-ui.select>

            <x-ui.select label="Matiere" name="subject_id">
                <option value="">Choisir une matiere</option>
                @foreach($subjects as $subject)
                    <option value="{{ $subject->id }}" @selected((int) old('subject_id', $homework->subject_id) === (int) $subject->id)>{{ $subject->name }}</option>
                @endforeach
            </x-ui.select>

            <x-ui.input label="Titre" name="title" :value="old('title', $homework->title)" required />
            <x-ui.textarea label="Description" name="description" rows="6">{{ old('description', $homework->description) }}</x-ui.textarea>
            <x-ui.input label="Date limite" name="due_at" type="datetime-local" :value="old('due_at', optional($homework->due_at)->format('Y-m-d\\TH:i'))" />

            <div class="flex justify-end gap-3">
                <x-ui.button :href="route($routePrefix . '.show', $homework)" variant="secondary">
                    Annuler
                </x-ui.button>
                <x-ui.button type="submit" variant="primary">
                    Enregistrer les modifications
                </x-ui.button>
            </div>
        </form>
    </x-ui.card>
</x-dynamic-component>
