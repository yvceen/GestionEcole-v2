<x-admin-layout title="Frais de l'eleve">
    <x-students.header
        title="Modifier les frais"
        subtitle="{{ $student->full_name }} - {{ $student->classroom?->name ?? '-' }} - Parent: {{ $student->parentUser?->name ?? '-' }}"
    >
        <x-ui.button :href="route('admin.students.index')" variant="ghost">Retour</x-ui.button>
    </x-students.header>

    @if($errors->any())
        <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-red-900">
            <ul class="list-disc space-y-1 pl-5 text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.students.fees.update', $student) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <x-ui.card title="Frais de base" subtitle="Mensualites et assurance">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-ui.input
                    label="Scolarite (mensuel)"
                    name="tuition_monthly"
                    type="number"
                    step="0.01"
                    :value="old('tuition_monthly', $student->feePlan->tuition_monthly)"
                    required
                />
                <x-ui.input
                    label="Transport (mensuel)"
                    name="transport_monthly"
                    type="number"
                    step="0.01"
                    :value="old('transport_monthly', $student->feePlan->transport_monthly)"
                />
                <x-ui.input
                    label="Cantine (mensuel)"
                    name="canteen_monthly"
                    type="number"
                    step="0.01"
                    :value="old('canteen_monthly', $student->feePlan->canteen_monthly)"
                />
                <x-ui.input
                    label="Assurance (annuel)"
                    name="insurance_yearly"
                    type="number"
                    step="0.01"
                    :value="old('insurance_yearly', $student->feePlan->insurance_yearly)"
                />
                <x-ui.input
                    label="Mois de debut"
                    name="starts_month"
                    type="number"
                    min="1"
                    max="12"
                    :value="old('starts_month', $student->feePlan->starts_month)"
                    required
                />
                <div class="flex items-end">
                    <div class="flex items-center gap-2">
                        <input type="hidden" name="insurance_paid" value="0">
                        <input id="insurance_paid" name="insurance_paid" type="checkbox" value="1" class="rounded border-slate-300 text-blue-600" {{ old('insurance_paid', $student->feePlan->insurance_paid ?? false) ? 'checked' : '' }}>
                        <label for="insurance_paid" class="text-sm font-semibold text-slate-700">Assurance annuelle payee</label>
                    </div>
                </div>
            </div>
            <div class="mt-4">
                <label for="notes" class="mb-1 block text-sm font-semibold text-slate-700">Remarques</label>
                <textarea id="notes" name="notes" rows="3" class="app-input">{{ old('notes', $student->feePlan->notes) }}</textarea>
            </div>
        </x-ui.card>

        <x-ui.card title="Transport scolaire" subtitle="Activation et affectation">
            <div class="space-y-4">
                <div class="flex items-center gap-2">
                    <input type="hidden" name="transport_enabled" value="0">
                    <input id="transport_enabled" name="transport_enabled" type="checkbox" value="1" class="rounded border-slate-300 text-blue-600" {{ old('transport_enabled', ($transportAssignment?->is_active ?? false)) ? 'checked' : '' }}>
                    <label for="transport_enabled" class="text-sm font-semibold text-slate-700">Transport ON</label>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="transport_route_id" class="mb-1 block text-sm font-semibold text-slate-700">Route</label>
                        <select id="transport_route_id" name="transport_route_id" class="app-input">
                            <option value="">-- Choisir --</option>
                            @foreach(($routes ?? collect()) as $route)
                                <option value="{{ $route->id }}" @selected((string)old('transport_route_id', $transportAssignment?->route_id) === (string)$route->id)>
                                    {{ $route->route_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="transport_vehicle_id" class="mb-1 block text-sm font-semibold text-slate-700">Chauffeur / Bus</label>
                        <select id="transport_vehicle_id" name="transport_vehicle_id" class="app-input">
                            <option value="">-- Auto depuis route --</option>
                            @foreach(($vehicles ?? collect()) as $vehicle)
                                <option value="{{ $vehicle->id }}" @selected((string)old('transport_vehicle_id', $transportAssignment?->vehicle_id) === (string)$vehicle->id)>
                                    {{ $vehicle->registration_number ?? $vehicle->plate_number ?? ('Vehicule #'.$vehicle->id) }}
                                    @if($vehicle->driver) - {{ $vehicle->driver->name }} @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="transport_period" class="mb-1 block text-sm font-semibold text-slate-700">Periode</label>
                        <select id="transport_period" name="transport_period" class="app-input">
                            <option value="both" @selected(old('transport_period', $transportAssignment?->period ?? 'both') === 'both')>Matin et Soir</option>
                            <option value="morning" @selected(old('transport_period', $transportAssignment?->period) === 'morning')>Matin</option>
                            <option value="evening" @selected(old('transport_period', $transportAssignment?->period) === 'evening')>Soir</option>
                        </select>
                    </div>
                    <x-ui.input label="Point de ramassage" name="transport_pickup_point" :value="old('transport_pickup_point', $transportAssignment?->pickup_point)" />
                </div>
            </div>
        </x-ui.card>

        <div class="flex justify-end">
            <x-ui.button type="submit" variant="primary">Enregistrer les frais</x-ui.button>
        </div>
    </form>
</x-admin-layout>
