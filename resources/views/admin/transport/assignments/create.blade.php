<x-admin-layout title="Nouvelle affectation">
    <x-ui.page-header
        title="Nouvelle affectation"
        subtitle="Associez un eleve a une route et a un vehicule dans un formulaire plus clair."
    >
        <x-slot name="actions">
            <x-ui.button :href="route('admin.transport.assignments.index')" variant="secondary">
                Retour
            </x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.card title="Affectation transport" subtitle="Choisissez l'eleve, la route et la periode de transport.">
        <form method="POST" action="{{ route('admin.transport.assignments.store') }}" class="space-y-5">
            @csrf

            <div class="app-field">
                <label class="app-label">Eleve</label>
                <select name="student_id" class="app-input @error('student_id') border-rose-500 @enderror" required data-control="search">
                    <option value="">Selectionner un eleve</option>
                    @foreach(($students ?? collect()) as $student)
                        <option value="{{ $student->id }}" @selected(old('student_id') == $student->id)>
                            {{ $student->full_name }} ({{ $student->classroom?->name ?? '-' }})
                        </option>
                    @endforeach
                </select>
                @error('student_id')<p class="app-error">{{ $message }}</p>@enderror
            </div>

            <div class="app-field">
                <label class="app-label">Vehicule</label>
                <select id="vehicle_id" name="vehicle_id" class="app-input @error('vehicle_id') border-rose-500 @enderror" required>
                    <option value="">Selectionner un vehicule</option>
                    @foreach(($vehicles ?? collect()) as $vehicle)
                        <option value="{{ $vehicle->id }}" @selected(old('vehicle_id') == $vehicle->id)>
                            {{ $vehicle->registration_number }} | {{ $vehicle->plate_number ?? '-' }} | Chauffeur : {{ $vehicle->driver?->name ?? '-' }}
                        </option>
                    @endforeach
                </select>
                @error('vehicle_id')<p class="app-error">{{ $message }}</p>@enderror
            </div>

            <div class="app-field">
                <label class="app-label">Route</label>
                <select id="route_id" name="route_id" class="app-input @error('route_id') border-rose-500 @enderror" required>
                    <option value="">Selectionner une route</option>
                    @foreach(($routes ?? collect()) as $route)
                        <option value="{{ $route->id }}" data-vehicle="{{ $route->vehicle_id }}" @selected(old('route_id') == $route->id)>
                            {{ $route->route_name }} ({{ $route->start_point }} → {{ $route->end_point }}) - {{ number_format($route->monthly_fee, 2, ',', ' ') }} DH
                        </option>
                    @endforeach
                </select>
                @error('route_id')<p class="app-error">{{ $message }}</p>@enderror
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="app-field">
                    <label class="app-label">Periode</label>
                    <select name="period" class="app-input @error('period') border-rose-500 @enderror" required>
                        <option value="both" @selected(old('period', 'both') == 'both')>Matin et soir</option>
                        <option value="morning" @selected(old('period') == 'morning')>Matin</option>
                        <option value="evening" @selected(old('period') == 'evening')>Soir</option>
                    </select>
                    @error('period')<p class="app-error">{{ $message }}</p>@enderror
                </div>

                <div class="app-field">
                    <label class="app-label">Date de debut</label>
                    <input type="date" name="assigned_date" value="{{ old('assigned_date', date('Y-m-d')) }}" class="app-input @error('assigned_date') border-rose-500 @enderror" required>
                    @error('assigned_date')<p class="app-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="app-field">
                <label class="app-label">Point de ramassage</label>
                <input type="text" name="pickup_point" value="{{ old('pickup_point') }}" class="app-input @error('pickup_point') border-rose-500 @enderror" placeholder="Ex. : Pres du stade municipal">
                @error('pickup_point')<p class="app-error">{{ $message }}</p>@enderror
            </div>

            <x-ui.alert variant="info">
                L'affectation sera active a partir de la date de debut. Vous pourrez la terminer ulterieurement.
            </x-ui.alert>

            <div class="flex items-center gap-3">
                <x-ui.button type="submit" variant="primary">Creer l'affectation</x-ui.button>
                <x-ui.button :href="route('admin.transport.assignments.index')" variant="secondary">Annuler</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    <script>
        const vehicleSelect = document.getElementById('vehicle_id');
        const routeSelect = document.getElementById('route_id');

        function filterRoutes() {
            const vehicleId = vehicleSelect.value;

            [...routeSelect.options].forEach(option => {
                if (!option.value) return;
                const routeVehicleId = option.getAttribute('data-vehicle');
                option.hidden = (vehicleId && routeVehicleId && routeVehicleId !== vehicleId);
            });

            if (routeSelect.selectedOptions.length && routeSelect.selectedOptions[0].hidden) {
                routeSelect.value = '';
            }
        }

        vehicleSelect?.addEventListener('change', filterRoutes);
        filterRoutes();
    </script>
</x-admin-layout>
