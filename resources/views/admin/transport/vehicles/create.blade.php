<x-admin-layout title="Ajouter un véhicule">
    <x-ui.page-header
        title="Ajouter un véhicule"
        subtitle="Créez une nouvelle fiche véhicule avec des informations claires et bien regroupées."
    >
        <x-slot name="actions">
            <x-ui.button :href="route('admin.transport.vehicles.index')" variant="secondary">
                Retour
            </x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.card title="Fiche véhicule" subtitle="Renseignez l'identification, la capacité et le conducteur.">
        <form method="POST" action="{{ route('admin.transport.vehicles.store') }}" class="space-y-5">
            @csrf

            <div class="grid gap-4 md:grid-cols-2">
                <div class="app-field">
                    <label class="app-label">Nom du vehicule</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="app-input @error('name') border-rose-500 @enderror" placeholder="Bus A - Primaire">
                    @error('name')<p class="app-error">{{ $message }}</p>@enderror
                </div>

                <div class="app-field">
                    <label class="app-label">Numéro d'immatriculation</label>
                    <input type="text" name="registration_number" value="{{ old('registration_number') }}" class="app-input @error('registration_number') border-rose-500 @enderror" placeholder="AB-123-CD" required>
                    @error('registration_number')<p class="app-error">{{ $message }}</p>@enderror
                </div>

                <div class="app-field">
                    <label class="app-label">Plaque minéralogique</label>
                    <input type="text" name="plate_number" value="{{ old('plate_number') }}" class="app-input @error('plate_number') border-rose-500 @enderror">
                    @error('plate_number')<p class="app-error">{{ $message }}</p>@enderror
                </div>

                <div class="app-field">
                    <label class="app-label">Type de véhicule</label>
                    <select name="vehicle_type" class="app-input @error('vehicle_type') border-rose-500 @enderror" required>
                        <option value="">Sélectionner</option>
                        <option value="bus" @selected(old('vehicle_type') === 'bus')>Bus</option>
                        <option value="van" @selected(old('vehicle_type') === 'van')>Minibus</option>
                        <option value="car" @selected(old('vehicle_type') === 'car')>Voiture</option>
                        <option value="truck" @selected(old('vehicle_type') === 'truck')>Camion</option>
                    </select>
                    @error('vehicle_type')<p class="app-error">{{ $message }}</p>@enderror
                </div>

                <div class="app-field">
                    <label class="app-label">Capacité (places)</label>
                    <input type="number" name="capacity" value="{{ old('capacity') }}" min="1" max="500" class="app-input @error('capacity') border-rose-500 @enderror" placeholder="45" required>
                    @error('capacity')<p class="app-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="app-field">
                <label class="app-label">Conducteur</label>
                <select name="driver_id" class="app-input @error('driver_id') border-rose-500 @enderror">
                    <option value="">Aucun conducteur</option>
                    @foreach(($drivers ?? collect()) as $driver)
                        <option value="{{ $driver->id }}" @selected(old('driver_id') == $driver->id)>{{ $driver->name }}</option>
                    @endforeach
                </select>
                @error('driver_id')<p class="app-error">{{ $message }}</p>@enderror
            </div>

            <div class="app-field">
                <label class="app-label">Assistant</label>
                <input type="text" name="assistant_name" value="{{ old('assistant_name') }}" class="app-input @error('assistant_name') border-rose-500 @enderror" placeholder="Nom assistant">
                @error('assistant_name')<p class="app-error">{{ $message }}</p>@enderror
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="app-field">
                    <label class="app-label">Couleur</label>
                    <input type="text" name="color" value="{{ old('color') }}" class="app-input @error('color') border-rose-500 @enderror" placeholder="Blanc">
                    @error('color')<p class="app-error">{{ $message }}</p>@enderror
                </div>

                <div class="app-field">
                    <label class="app-label">Année du modèle</label>
                    <input type="number" name="model_year" value="{{ old('model_year', date('Y')) }}" min="1990" max="{{ date('Y') + 1 }}" class="app-input @error('model_year') border-rose-500 @enderror">
                    @error('model_year')<p class="app-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="app-field">
                <label class="app-label">Notes</label>
                <textarea name="notes" rows="4" class="app-input @error('notes') border-rose-500 @enderror" placeholder="Notes supplémentaires...">{{ old('notes') }}</textarea>
                @error('notes')<p class="app-error">{{ $message }}</p>@enderror
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300 text-sky-700" {{ old('is_active', 1) ? 'checked' : '' }}>
                    Véhicule actif
                </label>
            </div>

            <div class="flex items-center gap-3">
                <x-ui.button type="submit" variant="primary">Créer le véhicule</x-ui.button>
                <x-ui.button :href="route('admin.transport.vehicles.index')" variant="secondary">Annuler</x-ui.button>
            </div>
        </form>
    </x-ui.card>
</x-admin-layout>
