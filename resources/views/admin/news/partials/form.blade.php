@if($errors->any())
    <div class="rounded-3xl border border-red-200 bg-red-50 p-4 text-red-900">
        <ul class="ml-5 list-disc">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid gap-6 xl:grid-cols-[minmax(0,1.2fr)_minmax(320px,0.9fr)]">
    <x-ui.card title="Contenu de l actualite" subtitle="Renseignez un titre clair, un resume et un contenu complet pour le mobile et le web.">
        <div class="app-form-stack">
            <x-ui.input label="Titre" name="title" :value="old('title', $news->title)" hint="Choisissez une formulation concise et facile a comprendre." />

            <div>
                <label class="app-label" for="summary">Resume / accroche</label>
                <textarea id="summary" name="summary" rows="3" class="app-input">{{ old('summary', $news->summary) }}</textarea>
                <p class="app-hint">Ce texte apparait dans les listes et apercus de lecture.</p>
            </div>

            <div>
                <label class="app-label" for="body">Contenu</label>
                <textarea id="body" name="body" rows="10" class="app-input">{{ old('body', $news->body) }}</textarea>
                <p class="app-hint">Ajoutez un message complet et structure pour les familles et les equipes.</p>
            </div>

            <div>
                <label class="app-label" for="cover">Image de couverture (optionnelle)</label>
                <input id="cover" type="file" name="cover" accept="image/*" class="app-input">
                <p class="app-hint">Une image claire aide a mettre en valeur l information dans l application mobile.</p>
                @if($news->cover_url)
                    <img src="{{ $news->cover_url }}" alt="Couverture actualite" class="mt-3 h-40 w-full rounded-2xl object-cover">
                @endif
            </div>
        </div>
    </x-ui.card>

    <x-ui.card title="Publication et audience" subtitle="Gardez la maitrise de la visibilite par ecole ou classe.">
        <div class="space-y-5">
            <x-ui.select label="Statut" name="status">
                @foreach(['draft' => 'Brouillon', 'published' => 'Publie', 'archived' => 'Archive'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $news->status ?? 'draft') === $value)>{{ $label }}</option>
                @endforeach
            </x-ui.select>

            <x-ui.input label="Date de publication" name="date" type="date" :value="old('date', optional($news->date)->format('Y-m-d'))" />

            <x-ui.select label="Audience" name="scope">
                <option value="school" @selected(old('scope', $news->scope ?? 'school') === 'school')>Toute l ecole</option>
                <option value="classroom" @selected(old('scope', $news->scope ?? '') === 'classroom')>Une classe</option>
            </x-ui.select>

            <x-ui.select label="Classe (si audience = classe)" name="classroom_id">
                <option value="">Choisir une classe</option>
                @foreach(($classrooms ?? collect()) as $classroom)
                    <option value="{{ $classroom->id }}" @selected((int) old('classroom_id', $news->classroom_id) === (int) $classroom->id)>
                        {{ $classroom->name }}
                    </option>
                @endforeach
            </x-ui.select>

            <label class="flex items-center gap-2">
                <input type="checkbox" name="is_pinned" value="1" class="rounded border-slate-300"
                       @checked(old('is_pinned', $news->is_pinned ?? false))>
                <span class="text-sm text-slate-700">Mettre en avant cette actualite</span>
            </label>

            @if($news->source_type)
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                    Source systeme : {{ $news->source_type }}
                </div>
            @endif
        </div>
    </x-ui.card>
</div>

<div class="app-form-actions">
    <p class="app-form-actions-copy">Verifiez la cible, le statut et la date avant de publier pour garantir une communication claire.</p>
    <div class="flex justify-end gap-3">
        <x-ui.button :href="url()->previous()" variant="secondary">Annuler</x-ui.button>
        <x-ui.button type="submit" variant="primary">{{ $news->exists ? 'Mettre a jour' : 'Enregistrer' }}</x-ui.button>
    </div>
</div>
