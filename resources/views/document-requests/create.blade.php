<x-dynamic-component :component="$layoutComponent" title="Nouvelle demande" subtitle="Demandez un certificat ou une attestation pour votre enfant.">
    <div class="mx-auto max-w-4xl">
        <section class="rounded-[28px] border border-sky-100 bg-gradient-to-r from-sky-50 via-white to-emerald-50 p-6 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-sky-700">Demande de document</p>
            <h1 class="mt-2 text-2xl font-bold text-slate-950">Quel document souhaitez-vous recevoir ?</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600">Choisissez l’enfant, le type de document et la méthode de remise. Vous pourrez suivre chaque étape depuis votre espace.</p>
        </section>

        <form method="POST" action="{{ route($routePrefix.'.store') }}" class="mt-6 space-y-6">
            @csrf
            <x-ui.card title="Informations de la demande" subtitle="Les champs marqués sont nécessaires à la préparation.">
                <div class="grid gap-5 md:grid-cols-2">
                    <label class="grid gap-2 text-sm font-semibold text-slate-700">Enfant
                        <select name="student_id" class="app-input" required>
                            <option value="">Choisir un enfant</option>
                            @foreach($children as $child)<option value="{{ $child->id }}" @selected(old('student_id') == $child->id)>{{ $child->full_name }}{{ $child->classroom ? ' · '.$child->classroom->name : '' }}</option>@endforeach
                        </select>
                    </label>
                    <label class="grid gap-2 text-sm font-semibold text-slate-700">Type de document
                        <select name="type" class="app-input" required>
                            @foreach(\App\Models\DocumentRequest::types() as $value => $label)<option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>@endforeach
                        </select>
                    </label>
                    <label class="grid gap-2 text-sm font-semibold text-slate-700">Nom du document si « Autre »
                        <input name="custom_type" value="{{ old('custom_type') }}" class="app-input" placeholder="Ex. Attestation pour dossier sportif">
                    </label>
                    <label class="grid gap-2 text-sm font-semibold text-slate-700">Langue
                        <select name="language" class="app-input"><option value="fr">Français</option><option value="ar">Arabe</option><option value="en">Anglais</option></select>
                    </label>
                    <label class="grid gap-2 text-sm font-semibold text-slate-700">Nombre d’exemplaires
                        <input type="number" min="1" max="5" name="copies" value="{{ old('copies', 1) }}" class="app-input">
                    </label>
                    <label class="grid gap-2 text-sm font-semibold text-slate-700">Méthode de remise
                        <select name="delivery_method" class="app-input"><option value="pickup">Retrait à l’école</option><option value="digital">Document numérique</option></select>
                    </label>
                    <label class="grid gap-2 text-sm font-semibold text-slate-700 md:col-span-2">Utilisation prévue ou précision
                        <textarea name="purpose" rows="4" class="app-input" placeholder="Ex. dossier d’inscription, demande administrative...">{{ old('purpose') }}</textarea>
                    </label>
                </div>
                @if($errors->any())<div class="mt-5 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">{{ $errors->first() }}</div>@endif
            </x-ui.card>
            <div class="flex flex-wrap justify-end gap-3"><x-ui.button :href="route($routePrefix.'.index')" variant="secondary">Annuler</x-ui.button><x-ui.button type="submit" variant="primary">Envoyer la demande</x-ui.button></div>
        </form>
    </div>
</x-dynamic-component>
