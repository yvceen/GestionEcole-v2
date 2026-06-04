<x-dynamic-component :component="$layoutComponent" title="Nouvelle autorisation" subtitle="Préparez une demande claire et choisissez les familles concernées.">
    <x-ui.card title="Créer et envoyer" subtitle="Les parents concernés recevront immédiatement une notification.">
        <form method="POST" action="{{ route($routePrefix.'.store') }}" class="space-y-6" x-data="{ target: @js(old('target_type', 'all')) }">
            @csrf
            <div class="grid gap-4 md:grid-cols-2">
                <x-ui.input name="title" label="Titre" :value="old('title')" required placeholder="Ex. : Sortie au musée des sciences" />
                <x-ui.select name="category" label="Type d’autorisation" required>
                    @foreach(\App\Models\DigitalAuthorization::categories() as $value => $label)<option value="{{ $value }}" @selected(old('category') === $value)>{{ $label }}</option>@endforeach
                </x-ui.select>
                <x-ui.input type="datetime-local" name="event_at" label="Date de l’activité" :value="old('event_at')" />
                <x-ui.input type="datetime-local" name="due_at" label="Réponse souhaitée avant" :value="old('due_at')" />
            </div>
            <x-ui.textarea name="description" label="Présentation destinée aux parents" rows="5" required placeholder="Expliquez clairement l’activité et ce que le parent autorise.">{{ old('description') }}</x-ui.textarea>
            <x-ui.textarea name="instructions" label="Informations pratiques" rows="3" placeholder="Tenue, horaire, documents à prévoir...">{{ old('instructions') }}</x-ui.textarea>

            <div class="rounded-2xl border border-sky-100 bg-sky-50 p-5">
                <h2 class="font-bold text-slate-950">Élèves concernés</h2>
                <div class="mt-4 grid gap-3 sm:grid-cols-3">
                    @foreach(['all' => 'Tous les élèves', 'classroom' => 'Une classe', 'students' => 'Sélection manuelle'] as $value => $label)
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-sky-100 bg-white p-3"><input type="radio" name="target_type" value="{{ $value }}" x-model="target"> <span class="font-semibold text-slate-800">{{ $label }}</span></label>
                    @endforeach
                </div>
                <div class="mt-4" x-show="target === 'classroom'" x-cloak><x-ui.select name="classroom_id" label="Classe"><option value="">Choisir une classe</option>@foreach($classrooms as $classroom)<option value="{{ $classroom->id }}">{{ $classroom->name }}</option>@endforeach</x-ui.select></div>
                <div class="mt-4" x-show="target === 'students'" x-cloak>
                    <label class="app-label">Trier les élèves</label>
                    <select name="student_ids[]" multiple size="10" class="app-input min-h-64">@foreach($students as $student)<option value="{{ $student->id }}">{{ $student->full_name }} — {{ $student->classroom?->name ?? 'Sans classe' }}</option>@endforeach</select>
                    <p class="mt-2 text-xs text-slate-500">Maintenez Ctrl pour sélectionner plusieurs élèves.</p>
                </div>
            </div>
            <label class="flex items-center gap-3 rounded-2xl border border-amber-100 bg-amber-50 p-4"><input type="hidden" name="requires_comment" value="0"><input type="checkbox" name="requires_comment" value="1" @checked(old('requires_comment'))><span class="font-semibold text-slate-800">Demander obligatoirement une remarque au parent</span></label>
            <div class="flex flex-wrap justify-end gap-3"><x-ui.button :href="route($routePrefix.'.index')" variant="secondary">Annuler</x-ui.button><x-ui.button type="submit" variant="primary">Envoyer aux parents</x-ui.button></div>
        </form>
    </x-ui.card>
</x-dynamic-component>
