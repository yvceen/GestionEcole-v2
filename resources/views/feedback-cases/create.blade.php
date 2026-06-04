<x-dynamic-component :component="$layoutComponent" title="Nouvelle réclamation ou suggestion" subtitle="Partagez une remarque, une difficulté ou une idée d’amélioration.">
    <div class="mx-auto max-w-4xl">
        <section class="rounded-[28px] border border-sky-100 bg-gradient-to-r from-sky-50 via-white to-emerald-50 p-6 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-sky-700">Votre retour compte</p>
            <h1 class="mt-2 text-2xl font-bold text-slate-950">Envoyer une demande</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600">Décrivez la situation clairement. L’établissement reçoit la demande avec un numéro de suivi.</p>
        </section>
        <form method="POST" action="{{ route($routePrefix.'.store') }}" class="mt-6 space-y-6">
            @csrf
            <x-ui.card title="Informations principales" subtitle="Choisissez le type et le thème de votre demande.">
                <div class="grid gap-5 md:grid-cols-2">
                    <label class="grid gap-2 text-sm font-semibold text-slate-700">Type
                        <select name="kind" class="app-input" required>@foreach(\App\Models\FeedbackCase::kinds() as $value => $label)<option value="{{ $value }}" @selected(old('kind') === $value)>{{ $label }}</option>@endforeach</select>
                    </label>
                    <label class="grid gap-2 text-sm font-semibold text-slate-700">Catégorie
                        <select name="category" class="app-input" required>@foreach(\App\Models\FeedbackCase::categories() as $value => $label)<option value="{{ $value }}" @selected(old('category') === $value)>{{ $label }}</option>@endforeach</select>
                    </label>
                    @if($children->isNotEmpty())
                        <label class="grid gap-2 text-sm font-semibold text-slate-700 md:col-span-2">Élève concerné
                            <select name="student_id" class="app-input"><option value="">Aucun élève précis</option>@foreach($children as $child)<option value="{{ $child->id }}" @selected(old('student_id') == $child->id)>{{ $child->full_name }}{{ $child->classroom ? ' · '.$child->classroom->name : '' }}</option>@endforeach</select>
                        </label>
                    @endif
                    <label class="grid gap-2 text-sm font-semibold text-slate-700 md:col-span-2">Sujet
                        <input name="subject" value="{{ old('subject') }}" class="app-input" required placeholder="Ex. Problème de communication, idée pour améliorer l’accueil...">
                    </label>
                    <label class="grid gap-2 text-sm font-semibold text-slate-700 md:col-span-2">Description
                        <textarea name="description" rows="7" class="app-input" required placeholder="Expliquez votre demande avec les informations importantes.">{{ old('description') }}</textarea>
                    </label>
                    <label class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700 md:col-span-2"><input type="checkbox" name="is_confidential" value="1" class="mt-1 rounded border-slate-300" @checked(old('is_confidential'))><span><strong>Demande confidentielle</strong><br><span class="text-slate-500">À utiliser si le sujet est sensible et doit rester limité à l’équipe de traitement.</span></span></label>
                </div>
                @if($errors->any())<div class="mt-5 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">{{ $errors->first() }}</div>@endif
            </x-ui.card>
            <div class="flex flex-wrap justify-end gap-3"><x-ui.button :href="route($routePrefix.'.index')" variant="secondary">Annuler</x-ui.button><x-ui.button type="submit" variant="primary">Envoyer</x-ui.button></div>
        </form>
    </div>
</x-dynamic-component>
