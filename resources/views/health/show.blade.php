@php($profile = $student->healthProfile)
<x-dynamic-component :component="$layoutComponent" :title="'Santé - '.$student->full_name" subtitle="Informations utiles, alertes actives et historique de santé.">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div><h1 class="text-2xl font-bold text-slate-950">{{ $student->full_name }}</h1><p class="mt-1 text-sm text-slate-500">{{ $student->classroom?->name ?? 'Sans classe' }} · Parent : {{ $student->parentUser?->name ?? '-' }} {{ $student->parentUser?->phone ? '· '.$student->parentUser->phone : '' }}</p></div>
        <x-ui.button :href="route($routePrefix.'.index')" variant="secondary">Retour aux situations</x-ui.button>
    </div>

    @if(session('success'))<x-ui.alert variant="success" class="mt-4">{{ session('success') }}</x-ui.alert>@endif
    @if($errors->any())<x-ui.alert variant="error" class="mt-4">{{ $errors->first() }}</x-ui.alert>@endif

    <section class="mt-5 grid gap-5 xl:grid-cols-[minmax(0,1fr)_380px]">
        <div class="space-y-5">
            <div class="rounded-[26px] border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-bold text-slate-950">Alertes et situations</h2>
                <div class="mt-4 space-y-3">
                    @forelse($student->healthReports->sortByDesc('starts_at') as $report)
                        @if(!$isDriver || $report->visible_to_driver)
                        <article class="rounded-2xl border {{ $report->status === 'active' ? 'border-amber-200 bg-amber-50' : 'border-slate-200 bg-slate-50' }} p-4">
                            <div class="flex flex-wrap items-start justify-between gap-3"><div><p class="font-bold text-slate-950">{{ $report->condition_name }}</p><p class="mt-1 text-xs text-slate-500">{{ $report->starts_at?->format('d/m/Y H:i') }} · {{ $report->reporter?->name ?? 'École' }}</p></div><x-ui.badge :variant="$report->status === 'active' ? (in_array($report->severity,['high','urgent']) ? 'danger' : 'warning') : 'success'">{{ $report->status === 'active' ? 'Active' : 'Terminée' }}</x-ui.badge></div>
                            @if($report->symptoms)<p class="mt-3 text-sm text-slate-700"><strong>Symptômes :</strong> {{ $report->symptoms }}</p>@endif
                            @if($report->instructions)<p class="mt-2 text-sm text-slate-700"><strong>Consignes :</strong> {{ $report->instructions }}</p>@endif
                            @if($canManage && $report->status === 'active')<form method="POST" action="{{ route($routePrefix.'.reports.resolve', $report) }}" class="mt-3">@csrf @method('PUT')<x-ui.button type="submit" size="sm" variant="secondary">Marquer comme terminée</x-ui.button></form>@endif
                        </article>
                        @endif
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 p-6 text-sm text-slate-500">Aucune situation enregistrée.</div>
                    @endforelse
                </div>
            </div>

            @if($canManage)
            <div class="rounded-[26px] border border-sky-100 bg-sky-50 p-6">
                <h2 class="text-xl font-bold text-slate-950">Mettre à jour le dossier permanent</h2>
                <form method="POST" action="{{ route($routePrefix.'.profile.update', $student) }}" class="mt-4 grid gap-4 md:grid-cols-2">@csrf @method('PUT')
                    <x-ui.input name="blood_type" label="Groupe sanguin" :value="$profile?->blood_type" />
                    <x-ui.input name="emergency_contact_phone" label="Téléphone d'urgence" :value="$profile?->emergency_contact_phone" />
                    <x-ui.textarea name="allergies" label="Allergies">{{ $profile?->allergies }}</x-ui.textarea>
                    <x-ui.textarea name="chronic_conditions" label="Maladies chroniques">{{ $profile?->chronic_conditions }}</x-ui.textarea>
                    <x-ui.textarea name="medications" label="Médicaments habituels">{{ $profile?->medications }}</x-ui.textarea>
                    <x-ui.textarea name="emergency_instructions" label="Consignes d'urgence">{{ $profile?->emergency_instructions }}</x-ui.textarea>
                    <x-ui.input name="emergency_contact_name" label="Contact d'urgence" :value="$profile?->emergency_contact_name" />
                    <x-ui.input name="emergency_contact_relationship" label="Lien avec l'élève" :value="$profile?->emergency_contact_relationship" />
                    <x-ui.input name="doctor_name" label="Médecin" :value="$profile?->doctor_name" />
                    <x-ui.input name="doctor_phone" label="Téléphone médecin" :value="$profile?->doctor_phone" />
                    <label class="flex items-center gap-3 rounded-2xl bg-white p-4 md:col-span-2"><input type="hidden" name="allow_first_aid" value="0"><input type="checkbox" name="allow_first_aid" value="1" @checked($profile?->allow_first_aid ?? true)> <span class="font-semibold text-slate-800">Premiers secours autorisés</span></label>
                    <div class="md:col-span-2"><x-ui.button type="submit">Enregistrer le dossier</x-ui.button></div>
                </form>
            </div>
            @endif
        </div>

        <aside class="space-y-5">
            @if(!$isDriver)
            <div class="rounded-[26px] border border-emerald-100 bg-emerald-50 p-5">
                <h2 class="font-bold text-slate-950">Informations essentielles</h2>
                <dl class="mt-4 space-y-3 text-sm"><div><dt class="text-slate-500">Groupe sanguin</dt><dd class="font-semibold text-slate-900">{{ $profile?->blood_type ?: 'Non renseigné' }}</dd></div><div><dt class="text-slate-500">Allergies</dt><dd class="font-semibold text-slate-900">{{ $profile?->allergies ?: 'Aucune signalée' }}</dd></div><div><dt class="text-slate-500">Maladies chroniques</dt><dd class="font-semibold text-slate-900">{{ $profile?->chronic_conditions ?: 'Aucune signalée' }}</dd></div><div><dt class="text-slate-500">Médicaments</dt><dd class="font-semibold text-slate-900">{{ $profile?->medications ?: 'Aucun signalé' }}</dd></div><div><dt class="text-slate-500">Consignes d'urgence</dt><dd class="font-semibold text-slate-900">{{ $profile?->emergency_instructions ?: 'Aucune' }}</dd></div></dl>
            </div>
            @endif
            @if($canReport)
            <div class="rounded-[26px] border border-amber-100 bg-amber-50 p-5">
                <h2 class="font-bold text-slate-950">{{ $canManage ? 'Ajouter une situation' : 'Signaler que mon enfant est malade' }}</h2>
                <form method="POST" action="{{ route($routePrefix.'.reports.store', $student) }}" class="mt-4 space-y-3">@csrf
                    <input type="hidden" name="type" value="illness"><x-ui.input name="condition_name" label="Maladie ou situation" required placeholder="Ex. : grippe, fièvre..." />
                    <x-ui.textarea name="symptoms" label="Symptômes"></x-ui.textarea><x-ui.textarea name="instructions" label="Consignes utiles"></x-ui.textarea>
                    <x-ui.select name="severity" label="Importance"><option value="low">Faible</option><option value="medium" selected>Moyenne</option><option value="high">Élevée</option><option value="urgent">Urgente</option></x-ui.select>
                    <x-ui.input name="expected_return_at" type="date" label="Retour prévu" />
                    <label class="flex items-center gap-2 text-sm font-semibold text-slate-700"><input type="hidden" name="visible_to_driver" value="0"><input type="checkbox" name="visible_to_driver" value="1"> Informer le chauffeur</label>
                    <x-ui.button type="submit" class="w-full justify-center">Enregistrer et prévenir</x-ui.button>
                </form>
            </div>
            @endif
        </aside>
    </section>
</x-dynamic-component>
