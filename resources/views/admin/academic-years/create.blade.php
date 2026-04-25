<x-admin-layout title="Nouvelle annee scolaire" subtitle="Preparez la prochaine annee sans toucher a l historique.">
    <x-ui.page-header title="Nouvelle annee scolaire" subtitle="Ajoutez une annee et choisissez si elle doit devenir l annee courante.">
        <x-slot name="actions">
            <x-ui.button :href="route('admin.academic-years.index')" variant="secondary">Retour aux annees</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.card title="Formulaire" subtitle="Les donnees existantes seront conservees.">
        <form method="POST" action="{{ route('admin.academic-years.store') }}" class="grid gap-4 md:grid-cols-2">
            @csrf
            <div class="md:col-span-2">
                <label class="mb-2 block text-sm font-medium text-slate-700" for="name">Nom</label>
                <x-ui.input id="name" name="name" :value="old('name', $defaults['name'])" placeholder="2026/2027" />
                @error('name')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700" for="starts_at">Debut</label>
                <x-ui.input id="starts_at" name="starts_at" type="date" :value="old('starts_at', $defaults['starts_at']->toDateString())" />
                @error('starts_at')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700" for="ends_at">Fin</label>
                <x-ui.input id="ends_at" name="ends_at" type="date" :value="old('ends_at', $defaults['ends_at']->toDateString())" />
                @error('ends_at')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700" for="status">Statut</label>
                <select id="status" name="status" class="app-input">
                    @foreach(\App\Models\AcademicYear::statuses() as $status)
                        <option value="{{ $status }}" @selected(old('status', 'draft') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
                @error('status')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
            <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700">
                <input type="checkbox" name="is_current" value="1" @checked(old('is_current')) class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                Definir cette annee comme annee courante
            </label>
            <div class="md:col-span-2 flex flex-wrap gap-3">
                <x-ui.button type="submit" variant="primary">Enregistrer</x-ui.button>
                <x-ui.button :href="route('admin.academic-years.index')" variant="secondary">Annuler</x-ui.button>
            </div>
        </form>
    </x-ui.card>
</x-admin-layout>
