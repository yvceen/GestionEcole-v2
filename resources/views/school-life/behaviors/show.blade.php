<x-school-life-layout :title="'Vie scolaire - ' . $student->full_name" subtitle="Remarques disciplinaires, retards et suivi comportemental.">
    <x-ui.page-header :title="$student->full_name" :subtitle="'Classe: ' . ($student->classroom?->name ?? '-') . ' | Parent: ' . ($student->parentUser?->name ?? '-')">
        <x-slot name="actions">
            <x-ui.button :href="route('school-life.students.index')" variant="secondary">Retour eleves</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_340px]">
        <x-ui.card title="Historique disciplinaire" subtitle="Liste des remarques enregistrees pour cet eleve.">
            <div class="space-y-3">
                @forelse($behaviors as $behavior)
                    <article class="rounded-2xl border border-slate-200 bg-white px-4 py-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <x-ui.badge :variant="$behavior->type === 'sanction' ? 'danger' : ($behavior->type === 'retard' ? 'warning' : 'info')">
                                {{ ucfirst($behavior->type) }}
                            </x-ui.badge>
                            @if($behavior->visible_to_parent)
                                <x-ui.badge variant="success">Visible parent</x-ui.badge>
                            @endif
                            <span class="text-xs text-slate-500">{{ $behavior->date?->format('d/m/Y') }} - {{ $behavior->author?->name ?? '-' }}</span>
                        </div>
                        <p class="mt-3 text-sm text-slate-700">{{ $behavior->description }}</p>

                        <details class="mt-4 rounded-2xl border border-slate-200 px-4 py-3">
                            <summary class="cursor-pointer text-xs font-semibold text-slate-700">Modifier cette remarque</summary>
                            <form method="POST" action="{{ route('school-life.students.behaviors.update', [$student, $behavior]) }}" class="mt-4 space-y-3">
                                @csrf
                                @method('PUT')
                                <select name="type" class="app-input">
                                    @foreach($types as $type)
                                        <option value="{{ $type }}" @selected($behavior->type === $type)>{{ ucfirst($type) }}</option>
                                    @endforeach
                                </select>
                                <x-ui.input name="date" type="date" :value="$behavior->date?->toDateString()" required />
                                <textarea name="description" rows="4" class="app-input">{{ $behavior->description }}</textarea>
                                <label class="flex items-center gap-2 text-sm text-slate-700">
                                    <input type="checkbox" name="visible_to_parent" value="1" class="rounded border-slate-300" @checked($behavior->visible_to_parent)>
                                    Visible pour le parent
                                </label>
                                <div class="flex items-center justify-between gap-3">
                                    <x-ui.button type="submit" variant="primary">Enregistrer</x-ui.button>
                                </div>
                            </form>
                            <form method="POST" action="{{ route('school-life.students.behaviors.destroy', [$student, $behavior]) }}" class="mt-3" onsubmit="return confirm('Supprimer cette remarque ?')">
                                @csrf
                                @method('DELETE')
                                <x-ui.button type="submit" variant="danger">Supprimer</x-ui.button>
                            </form>
                        </details>
                    </article>
                @empty
                    <div class="student-empty">Aucune remarque saisie pour cet eleve.</div>
                @endforelse
            </div>
            <div class="mt-4">{{ $behaviors->links() }}</div>
        </x-ui.card>

        <x-ui.card title="Ajouter une remarque" subtitle="Saisie rapide pour la vie scolaire ou enseignant.">
            <form method="POST" action="{{ route('school-life.students.behaviors.store', $student) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="app-label" for="type">Type</label>
                    <select id="type" name="type" class="app-input" required>
                        @foreach($types as $type)
                            <option value="{{ $type }}" @selected(old('type') === $type)>{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                </div>
                <x-ui.input id="date" name="date" type="date" label="Date" :value="old('date', now()->toDateString())" required />
                <div>
                    <label class="app-label" for="description">Description</label>
                    <textarea id="description" name="description" rows="5" class="app-input" required>{{ old('description') }}</textarea>
                </div>
                <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                    <input type="checkbox" name="notify_parent" value="1" checked class="rounded border-slate-300 text-sky-700">
                    Notifier le parent
                </label>
                <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                    <input type="checkbox" name="visible_to_parent" value="1" class="rounded border-slate-300 text-sky-700">
                    Rendre visible dans les espaces parent / eleve
                </label>
                <x-ui.button type="submit" variant="primary">Enregistrer remarque</x-ui.button>
            </form>
        </x-ui.card>
    </section>
</x-school-life-layout>
