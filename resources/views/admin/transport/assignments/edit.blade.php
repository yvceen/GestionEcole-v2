<x-admin-layout title="Modifier l'affectation">
    <x-ui.page-header
        title="Modifier l'affectation"
        :subtitle="$transportAssignment->student?->full_name"
    >
        <x-slot name="actions">
            <x-ui.button :href="route('admin.transport.assignments.index')" variant="secondary">
                Retour
            </x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.card title="Affectation transport" subtitle="Ajustez la route, la période et l'état de l'affectation.">
        <form method="POST" action="{{ route('admin.transport.assignments.update', $transportAssignment) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Élève</p>
                <p class="mt-2 font-semibold text-slate-900">{{ $transportAssignment->student?->full_name }}</p>
                <p class="text-xs text-slate-500">{{ $transportAssignment->student?->classroom?->name }}</p>
                <input type="hidden" name="student_id" value="{{ $transportAssignment->student_id }}">
            </div>

            <div class="app-field">
                <label class="app-label">Route</label>
                <select name="route_id" class="app-input @error('route_id') border-rose-500 @enderror" required>
                    <option value="">Sélectionner une route</option>
                    @foreach(($routes ?? collect()) as $route)
                        <option value="{{ $route->id }}" @selected(old('route_id', $transportAssignment->route_id) == $route->id)>
                            {{ $route->route_name }} ({{ $route->start_point }} → {{ $route->end_point }})
                        </option>
                    @endforeach
                </select>
                @error('route_id')<p class="app-error">{{ $message }}</p>@enderror
            </div>

            <div class="app-field">
                <label class="app-label">Point de ramassage</label>
                <input type="text" name="pickup_point" value="{{ old('pickup_point', $transportAssignment->pickup_point) }}" class="app-input @error('pickup_point') border-rose-500 @enderror">
                @error('pickup_point')<p class="app-error">{{ $message }}</p>@enderror
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="app-field">
                    <label class="app-label">Date de début</label>
                    <input type="date" name="assigned_date" value="{{ old('assigned_date', optional($transportAssignment->assigned_date)->format('Y-m-d')) }}" class="app-input @error('assigned_date') border-rose-500 @enderror" required>
                    @error('assigned_date')<p class="app-error">{{ $message }}</p>@enderror
                </div>

                <div class="app-field">
                    <label class="app-label">Date de fin</label>
                    <input type="date" name="ended_date" value="{{ old('ended_date', optional($transportAssignment->ended_date)->format('Y-m-d')) }}" class="app-input @error('ended_date') border-rose-500 @enderror">
                    @error('ended_date')<p class="app-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300 text-sky-700" {{ old('is_active', $transportAssignment->is_active && !$transportAssignment->ended_date) ? 'checked' : '' }}>
                    Affectation active
                </label>
            </div>

            <x-ui.alert variant="info">
                Vous pouvez définir une date de fin pour clôturer cette affectation.
            </x-ui.alert>

            <div class="flex items-center gap-3">
                <x-ui.button type="submit" variant="primary">Mettre à jour</x-ui.button>
                <x-ui.button :href="route('admin.transport.assignments.index')" variant="secondary">Annuler</x-ui.button>
            </div>
        </form>
    </x-ui.card>
</x-admin-layout>
