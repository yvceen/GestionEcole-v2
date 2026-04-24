<x-admin-layout title="Modifier le véhicule">
    <x-ui.page-header
        title="Modifier le véhicule"
        subtitle="Mettez à jour les caractéristiques du véhicule sans changer le fonctionnement du module."
    >
        <x-slot name="actions">
            <x-ui.button :href="route('admin.transport.vehicles.index')" variant="secondary">
                Retour
            </x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.card title="Fiche véhicule" :subtitle="$vehicle->registration_number">
        <form method="POST" action="{{ route('admin.transport.vehicles.update', $vehicle) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="grid gap-4 md:grid-cols-2">
                <div class="app-field">
                    <label class="app-label">Nom du vehicule</label>
                    <input type="text" name="name" value="{{ old('name', $vehicle->name) }}" class="app-input @error('name') border-rose-500 @enderror">
                    @error('name')<p class="app-error">{{ $message }}</p>@enderror
                </div>

                <div class="app-field">
                    <label class="app-label">Numéro d'immatriculation</label>
                    <input type="text" name="registration_number" value="{{ old('registration_number', $vehicle->registration_number) }}" class="app-input @error('registration_number') border-rose-500 @enderror" required>
                    @error('registration_number')<p class="app-error">{{ $message }}</p>@enderror
                </div>

                <div class="app-field">
                    <label class="app-label">Plaque minéralogique</label>
                    <input type="text" name="plate_number" value="{{ old('plate_number', $vehicle->plate_number) }}" class="app-input @error('plate_number') border-rose-500 @enderror">
                    @error('plate_number')<p class="app-error">{{ $message }}</p>@enderror
                </div>

                <div class="app-field">
                    <label class="app-label">Type de véhicule</label>
                    <select name="vehicle_type" class="app-input @error('vehicle_type') border-rose-500 @enderror" required>
                        <option value="bus" @selected(old('vehicle_type', $vehicle->vehicle_type) === 'bus')>Bus</option>
                        <option value="van" @selected(old('vehicle_type', $vehicle->vehicle_type) === 'van')>Minibus</option>
                        <option value="car" @selected(old('vehicle_type', $vehicle->vehicle_type) === 'car')>Voiture</option>
                        <option value="truck" @selected(old('vehicle_type', $vehicle->vehicle_type) === 'truck')>Camion</option>
                    </select>
                    @error('vehicle_type')<p class="app-error">{{ $message }}</p>@enderror
                </div>

                <div class="app-field">
                    <label class="app-label">Capacité (places)</label>
                    <input type="number" name="capacity" value="{{ old('capacity', $vehicle->capacity) }}" min="1" max="500" class="app-input @error('capacity') border-rose-500 @enderror" required>
                    @error('capacity')<p class="app-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="app-field">
                <label class="app-label">Conducteur</label>
                <select name="driver_id" class="app-input @error('driver_id') border-rose-500 @enderror">
                    <option value="">Aucun conducteur</option>
                    @foreach(($drivers ?? collect()) as $driver)
                        <option value="{{ $driver->id }}" @selected(old('driver_id', $vehicle->driver_id) == $driver->id)>{{ $driver->name }}</option>
                    @endforeach
                </select>
                @error('driver_id')<p class="app-error">{{ $message }}</p>@enderror
            </div>

            <div class="app-field">
                <label class="app-label">Assistant</label>
                <input type="text" name="assistant_name" value="{{ old('assistant_name', $vehicle->assistant_name) }}" class="app-input @error('assistant_name') border-rose-500 @enderror">
                @error('assistant_name')<p class="app-error">{{ $message }}</p>@enderror
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="app-field">
                    <label class="app-label">Couleur</label>
                    <input type="text" name="color" value="{{ old('color', $vehicle->color) }}" class="app-input @error('color') border-rose-500 @enderror">
                    @error('color')<p class="app-error">{{ $message }}</p>@enderror
                </div>

                <div class="app-field">
                    <label class="app-label">Année du modèle</label>
                    <input type="number" name="model_year" value="{{ old('model_year', $vehicle->model_year) }}" min="1990" max="{{ date('Y') + 1 }}" class="app-input @error('model_year') border-rose-500 @enderror">
                    @error('model_year')<p class="app-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="app-field">
                <label class="app-label">Notes</label>
                <textarea name="notes" rows="4" class="app-input @error('notes') border-rose-500 @enderror">{{ old('notes', $vehicle->notes) }}</textarea>
                @error('notes')<p class="app-error">{{ $message }}</p>@enderror
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300 text-sky-700" {{ old('is_active', $vehicle->is_active) ? 'checked' : '' }}>
                    Véhicule actif
                </label>
            </div>

            <div class="flex items-center gap-3">
                <x-ui.button type="submit" variant="primary">Mettre à jour</x-ui.button>
                <x-ui.button :href="route('admin.transport.vehicles.index')" variant="secondary">Annuler</x-ui.button>
            </div>
        </form>
    </x-ui.card>
</x-admin-layout>
