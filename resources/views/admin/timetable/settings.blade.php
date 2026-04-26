@php
    $layoutComponent = $layoutComponent ?? 'admin-layout';
    $routePrefix = $routePrefix ?? 'admin.timetable';
@endphp

<x-dynamic-component :component="$layoutComponent" title="Parametres emploi du temps">
    <x-ui.page-header
        title="Parametres emploi du temps et presences"
        subtitle="Definissez la journee type, les seances, les heures souples et les regles de retard/absence."
    />

    @if($errors->any())
        <x-ui.alert variant="error">
            <ul class="space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-ui.alert>
    @endif

    <x-ui.card title="Reglages generaux" subtitle="Ces parametres servent de base a l agenda, aux creneaux et aux regles de pointage.">
        <form method="POST" action="{{ route($routePrefix . '.settings.update') }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid gap-4 md:grid-cols-2">
                <x-ui.input id="day_start_time" type="time" name="day_start_time" label="Heure de debut" :value="old('day_start_time', substr((string) $settings->day_start_time, 0, 5))" required />
                <x-ui.input id="day_end_time" type="time" name="day_end_time" label="Heure de fin" :value="old('day_end_time', substr((string) $settings->day_end_time, 0, 5))" required />
                <x-ui.input id="late_grace_minutes" type="number" min="0" max="120" step="1" name="late_grace_minutes" label="Delai de grace retard (minutes)" :value="old('late_grace_minutes', (int) ($settings->late_grace_minutes ?? 15))" required hint="Ex: 15 = retard a partir de 08:15 si debut a 08:00." />
                <x-ui.input id="auto_absent_cutoff_time" type="time" name="auto_absent_cutoff_time" label="Heure limite auto absent (optionnel)" :value="old('auto_absent_cutoff_time', $settings->auto_absent_cutoff_time ? substr((string) $settings->auto_absent_cutoff_time, 0, 5) : '')" hint="Si vide, la derniere fin de session ou 09:00 sera utilisee." />
                <x-ui.input id="slot_minutes" type="number" min="30" max="180" step="5" name="slot_minutes" label="Duree d'une seance (minutes)" :value="old('slot_minutes', (int) $settings->slot_minutes)" required hint="Valeurs conseillees : 45, 50 ou 60 minutes." />
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-600">
                    <p class="font-semibold text-slate-900">Conseil</p>
                    <p class="mt-1 text-xs leading-5">Vous pouvez garder une plage globale large puis definir des sessions de presence plus fines juste en dessous.</p>
                </div>
                <x-ui.input id="lunch_start" type="time" name="lunch_start" label="Pause dejeuner - debut" :value="old('lunch_start', $settings->lunch_start ? substr((string) $settings->lunch_start, 0, 5) : '')" />
                <x-ui.input id="lunch_end" type="time" name="lunch_end" label="Pause dejeuner - fin" :value="old('lunch_end', $settings->lunch_end ? substr((string) $settings->lunch_end, 0, 5) : '')" />
            </div>

            <div class="rounded-3xl border border-slate-200 bg-slate-50 px-5 py-5">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Sessions de presence horaire</h3>
                        <p class="mt-1 text-xs text-slate-500">Definissez plusieurs plages dans la journee si l ecole fonctionne en seances distinctes.</p>
                    </div>
                    <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                        <input type="checkbox" name="allow_manual_time_override" value="1" class="rounded border-slate-300" @checked(old('allow_manual_time_override', $settings->allow_manual_time_override ?? true))>
                        Autoriser le override manuel
                    </label>
                </div>

                @php
                    $sessions = old('attendance_sessions', $settings->attendance_sessions ?? [['label' => 'Matin', 'start' => '08:00', 'end' => '12:00'], ['label' => 'Apres-midi', 'start' => '14:00', 'end' => '18:00']]);
                @endphp
                <div class="mt-4 space-y-3">
                    @foreach($sessions as $index => $session)
                        <div class="grid gap-3 md:grid-cols-3">
                            <x-ui.input :id="'attendance-session-label-'.$index" type="text" :name="'attendance_sessions['.$index.'][label]'" label="Libelle" :value="data_get($session, 'label')" />
                            <x-ui.input :id="'attendance-session-start-'.$index" type="time" :name="'attendance_sessions['.$index.'][start]'" label="Debut" :value="data_get($session, 'start')" />
                            <x-ui.input :id="'attendance-session-end-'.$index" type="time" :name="'attendance_sessions['.$index.'][end]'" label="Fin" :value="data_get($session, 'end')" />
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center gap-3 border-t border-slate-200 pt-4">
                <x-ui.button type="submit" variant="primary">Enregistrer</x-ui.button>
                <x-ui.button :href="route($routePrefix . '.index')" variant="secondary">Retour planning</x-ui.button>
            </div>
        </form>
    </x-ui.card>
</x-dynamic-component>
