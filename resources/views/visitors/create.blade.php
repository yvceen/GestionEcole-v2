<x-dynamic-component :component="$layoutComponent" title="Enregistrer un visiteur" subtitle="Préparez une visite ou confirmez une arrivée immédiate.">
    <x-ui.card title="Nouvelle visite" subtitle="Les informations servent uniquement à sécuriser l’accueil et le suivi de présence.">
        <form method="POST" action="{{ route($routePrefix.'.store') }}" class="space-y-6" x-data="{ arrival: @js(old('arrival_mode', 'now')) }">
            @csrf
            <div class="grid gap-3 sm:grid-cols-2">
                <label class="cursor-pointer rounded-2xl border border-emerald-200 bg-emerald-50 p-4"><input type="radio" name="arrival_mode" value="now" x-model="arrival"> <span class="ml-2 font-bold text-emerald-900">Le visiteur est arrivé</span></label>
                <label class="cursor-pointer rounded-2xl border border-sky-200 bg-sky-50 p-4"><input type="radio" name="arrival_mode" value="expected" x-model="arrival"> <span class="ml-2 font-bold text-sky-900">Prévoir une visite</span></label>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <x-ui.input name="visitor_name" label="Nom complet du visiteur" :value="old('visitor_name')" required />
                <x-ui.input name="phone" label="Téléphone" :value="old('phone')" />
                <x-ui.select name="identity_type" label="Type de pièce d’identité"><option value="">Non renseigné</option><option value="cin">CIN</option><option value="passport">Passeport</option><option value="permit">Permis de conduire</option><option value="other">Autre</option></x-ui.select>
                <x-ui.input name="identity_number" label="Numéro d’identité" :value="old('identity_number')" />
                <x-ui.input name="organization" label="Organisation ou entreprise" :value="old('organization')" />
                <x-ui.input name="vehicle_plate" label="Immatriculation du véhicule" :value="old('vehicle_plate')" />
                <x-ui.select name="purpose" label="Motif principal" required>@foreach(\App\Models\VisitorVisit::purposes() as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</x-ui.select>
                <div x-show="arrival === 'expected'" x-cloak><x-ui.input type="datetime-local" name="expected_at" label="Date et heure prévues" :value="old('expected_at')" /></div>
                <x-ui.select name="host_user_id" label="Personne visitée"><option value="">Accueil général</option>@foreach($hosts as $host)<option value="{{ $host->id }}">{{ $host->name }} — {{ \App\Models\User::labelForRole($host->role) }}</option>@endforeach</x-ui.select>
                <x-ui.select name="student_id" label="Élève concerné"><option value="">Aucun élève</option>@foreach($students as $student)<option value="{{ $student->id }}">{{ $student->full_name }} — {{ $student->classroom?->name ?? 'Sans classe' }}</option>@endforeach</x-ui.select>
            </div>
            <x-ui.textarea name="purpose_details" label="Détails de la visite" rows="3">{{ old('purpose_details') }}</x-ui.textarea>
            <x-ui.textarea name="entry_note" label="Note interne d’accueil" rows="2">{{ old('entry_note') }}</x-ui.textarea>
            <div class="flex flex-wrap justify-end gap-3"><x-ui.button :href="route($routePrefix.'.index')" variant="secondary">Annuler</x-ui.button><x-ui.button type="submit" variant="primary">Enregistrer la visite</x-ui.button></div>
        </form>
    </x-ui.card>
</x-dynamic-component>
