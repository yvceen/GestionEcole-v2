<x-dynamic-component :component="$context['layout']" :title="$context['title']" :subtitle="$context['subtitle']">
    <x-ui.page-header
        title="Liste des pieces d inscription"
        subtitle="Gerez les documents et fournitures remis aux familles, puis generez une version imprimable aux couleurs de l etablissement."
    >
        <div class="flex flex-wrap gap-3">
            <x-ui.button :href="route($context['routes']['preview'])" variant="secondary">Apercu imprimable</x-ui.button>
            <x-ui.button :href="route($context['routes']['pdf'])" variant="primary">Exporter en PDF</x-ui.button>
        </div>
    </x-ui.page-header>

    <div class="grid gap-6 xl:grid-cols-[360px_minmax(0,1fr)]">
        <x-ui.card title="Ajouter un element" subtitle="Structurez la liste par categorie, obligation et note pratique.">
            <form method="POST" action="{{ route($context['routes']['store']) }}" class="space-y-4">
                @csrf
                <x-ui.select label="Categorie" name="category" required>
                    @foreach($categoryOptions as $option)
                        <option value="{{ $option }}" @selected(old('category') === $option)>{{ $option }}</option>
                    @endforeach
                </x-ui.select>
                <x-ui.input label="Libelle" name="label" :value="old('label')" required />
                <x-ui.textarea label="Note / precision" name="notes" rows="4">{{ old('notes') }}</x-ui.textarea>
                <label class="flex items-center gap-3 rounded-2xl border border-slate-200 px-4 py-3">
                    <input type="checkbox" name="is_required" value="1" @checked(old('is_required', true))>
                    <span class="text-sm font-medium text-slate-700">Element obligatoire</span>
                </label>
                <label class="flex items-center gap-3 rounded-2xl border border-slate-200 px-4 py-3">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))>
                    <span class="text-sm font-medium text-slate-700">Visible dans le document final</span>
                </label>
                <div class="flex justify-end">
                    <x-ui.button type="submit" variant="primary">Ajouter a la liste</x-ui.button>
                </div>
            </form>
        </x-ui.card>

        <div class="space-y-5">
            @forelse($groupedItems as $category => $categoryItems)
                <x-ui.card :title="$category" :subtitle="$categoryItems->count() . ' element(s)'">
                    <div class="space-y-4">
                        @foreach($categoryItems as $item)
                            <form method="POST" action="{{ route($context['routes']['update'], $item) }}" class="rounded-3xl border border-slate-200 bg-slate-50/80 p-4">
                                @csrf
                                @method('PUT')
                                <div class="grid gap-4 lg:grid-cols-[180px_minmax(0,1fr)_220px]">
                                    <x-ui.select label="Categorie" name="category" required>
                                        @foreach($categoryOptions as $option)
                                            <option value="{{ $option }}" @selected($item->category === $option)>{{ $option }}</option>
                                        @endforeach
                                    </x-ui.select>
                                    <x-ui.input label="Libelle" name="label" :value="$item->label" required />
                                    <x-ui.input label="Position" name="sort_order_display" :value="'#' . $item->sort_order" disabled />
                                </div>
                                <div class="mt-4">
                                    <x-ui.textarea label="Note / precision" name="notes" rows="3">{{ $item->notes }}</x-ui.textarea>
                                </div>
                                <div class="mt-4 flex flex-wrap items-center gap-3">
                                    <label class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700">
                                        <input type="checkbox" name="is_required" value="1" @checked($item->is_required)>
                                        Obligatoire
                                    </label>
                                    <label class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700">
                                        <input type="checkbox" name="is_active" value="1" @checked($item->is_active)>
                                        Visible
                                    </label>
                                    <div class="ml-auto flex flex-wrap gap-2">
                                        <x-ui.button type="submit" variant="secondary">Enregistrer</x-ui.button>
                                    </div>
                                </div>
                            </form>
                            <div class="-mt-2 flex justify-end gap-2">
                                <form method="POST" action="{{ route($context['routes']['move'], [$item, 'direction' => 'up']) }}">
                                    @csrf
                                    <button type="submit" class="app-button-secondary rounded-full px-4 py-2 text-sm font-semibold">Monter</button>
                                </form>
                                <form method="POST" action="{{ route($context['routes']['move'], [$item, 'direction' => 'down']) }}">
                                    @csrf
                                    <button type="submit" class="app-button-secondary rounded-full px-4 py-2 text-sm font-semibold">Descendre</button>
                                </form>
                                <form method="POST" action="{{ route($context['routes']['destroy'], $item) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-full bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-700">Supprimer</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </x-ui.card>
            @empty
                <x-ui.empty-state
                    title="Aucune piece configuree"
                    description="Ajoutez la premiere liste d inscription pour commencer la generation et l impression."
                />
            @endforelse
        </div>
    </div>
</x-dynamic-component>
