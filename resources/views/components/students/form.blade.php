@props([
    'mode' => 'create',
    'action',
    'method' => 'POST',
    'student' => null,
    'classrooms' => collect(),
    'parents' => collect(),
    'routes' => collect(),
    'vehicles' => collect(),
    'transportAssignment' => null,
])

@php
    $isEdit = $mode === 'edit';
    $fee = $student?->feePlan;
@endphp

@if(session('success'))
    <x-ui.alert variant="success">{{ session('success') }}</x-ui.alert>
@endif

@if($errors->any())
    <x-ui.alert variant="error">
        <p class="font-semibold">Veuillez corriger les erreurs suivantes.</p>
        <ul class="mt-2 list-disc pl-5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-ui.alert>
@endif

<form id="studentForm" method="POST" action="{{ $action }}" class="space-y-6 pb-24">
    @csrf
    @if(strtoupper($method) !== 'POST')
        @method($method)
    @endif

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <x-ui.card title="Informations generales" subtitle="Identite et affectation de l'eleve.">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <x-ui.input
                        label="Nom complet"
                        name="full_name"
                        :value="old('full_name', $student->full_name ?? '')"
                        required
                        placeholder="Ex. : Amine El Fassi"
                    />
                </div>

                <x-ui.input
                    label="Date de naissance"
                    name="birth_date"
                    type="date"
                    :value="old('birth_date', $student->birth_date ?? '')"
                />

                <x-ui.select label="Genre" name="gender">
                    <option value="">Non renseigne</option>
                    <option value="male" @selected(old('gender', $student->gender ?? '') === 'male')>Garcon</option>
                    <option value="female" @selected(old('gender', $student->gender ?? '') === 'female')>Fille</option>
                </x-ui.select>

                <div class="md:col-span-2">
                    <x-ui.select label="Classe" name="classroom_id" required>
                        <option value="">Choisir une classe</option>
                        @foreach($classrooms as $classroom)
                            <option value="{{ $classroom->id }}" @selected((string) old('classroom_id', $student->classroom_id ?? '') === (string) $classroom->id)>
                                {{ $classroom->name }}
                            </option>
                        @endforeach
                    </x-ui.select>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card title="Parent" subtitle="Liaison du parent principal.">
            @if($isEdit)
                <x-ui.select label="Parent" name="parent_user_id">
                    <option value="">Aucun parent lie</option>
                    @foreach($parents as $parent)
                        <option value="{{ $parent->id }}" @selected((string) old('parent_user_id', $student->parent_user_id ?? '') === (string) $parent->id)>
                            {{ $parent->name }} ({{ $parent->email }})
                        </option>
                    @endforeach
                </x-ui.select>
            @else
                <div class="space-y-4">
                    <div class="relative">
                        <x-ui.input
                            label="Recherche rapide"
                            name="parent_label"
                            id="parentSearch"
                            :value="old('parent_label')"
                            placeholder="Nom ou email du parent"
                            autocomplete="off"
                            hint="Saisissez au moins 2 caracteres pour afficher des suggestions."
                        />

                        <input type="hidden" id="parentId" name="parent_user_id" value="{{ old('parent_user_id') }}">
                        <div id="parentResults" class="absolute z-20 mt-2 hidden w-full overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg"></div>
                    </div>

                    <x-ui.select label="Ou selectionner un parent existant" name="existing_parent_user_id">
                        <option value="">Choisir un parent</option>
                        @foreach($parents as $parent)
                            <option value="{{ $parent->id }}" @selected((string) old('existing_parent_user_id') === (string) $parent->id)>
                                {{ $parent->name }} - {{ $parent->email }}
                            </option>
                        @endforeach
                    </x-ui.select>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <div class="flex items-center gap-2">
                            <input type="hidden" name="create_parent_account" value="0">
                            <input id="create_parent_account" type="checkbox" name="create_parent_account" value="1" class="rounded border-slate-300 text-sky-700" {{ old('create_parent_account') ? 'checked' : '' }}>
                            <label for="create_parent_account" class="text-sm font-semibold text-slate-700">Creer un nouveau parent</label>
                        </div>

                        <div class="mt-4 grid grid-cols-1 gap-3">
                            <x-ui.input label="Nom du parent" name="parent_name" :value="old('parent_name')" />
                            <x-ui.input label="Email du parent" name="parent_email" type="email" :value="old('parent_email')" />
                            <div>
                                <x-ui.input id="parent_password" label="Mot de passe parent" name="parent_password" type="text" />
                                <x-ui.password-tools target="parent_password" helper="Mot de passe provisoire du parent, a conserver avant creation du compte." />
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </x-ui.card>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <x-ui.card title="Compte eleve" subtitle="Acces portail eleve, optionnel.">
            @if($isEdit)
                <p class="text-sm text-slate-600">
                    Compte actuel :
                    <span class="font-semibold text-slate-900">{{ $student?->studentUser?->email ?? 'Aucun compte lie' }}</span>
                </p>
            @else
                <div class="space-y-4">
                    <div class="flex items-center gap-2">
                        <input type="hidden" name="create_student_account" value="0">
                        <input id="create_student_account" type="checkbox" name="create_student_account" value="1" class="rounded border-slate-300 text-sky-700" {{ old('create_student_account') ? 'checked' : '' }}>
                        <label for="create_student_account" class="text-sm font-semibold text-slate-700">Creer un compte eleve</label>
                    </div>

                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <x-ui.input label="Email eleve" name="student_account_email" type="email" :value="old('student_account_email')" />
                        <div>
                            <x-ui.input id="student_account_password" label="Mot de passe eleve" name="student_account_password" type="text" />
                            <x-ui.password-tools target="student_account_password" helper="Mot de passe provisoire du compte eleve, a copier avant validation." />
                        </div>
                    </div>
                </div>
            @endif
        </x-ui.card>

        <x-ui.card title="Transport" subtitle="Options de transport scolaire.">
            <div class="space-y-4">
                <div class="flex items-center gap-2">
                    <input type="hidden" name="transport_enabled" value="0">
                    <input id="transport_enabled" type="checkbox" name="transport_enabled" value="1" class="rounded border-slate-300 text-sky-700" {{ old('transport_enabled', $transportAssignment?->is_active ?? false) ? 'checked' : '' }}>
                    <label for="transport_enabled" class="text-sm font-semibold text-slate-700">Transport scolaire actif</label>
                </div>

                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <x-ui.select label="Route" name="transport_route_id">
                        <option value="">Choisir une route</option>
                        @foreach(($routes ?? collect()) as $route)
                            <option value="{{ $route->id }}" @selected((string) old('transport_route_id', $transportAssignment?->route_id) === (string) $route->id)>
                                {{ $route->route_name }}
                            </option>
                        @endforeach
                    </x-ui.select>

                    <x-ui.select label="Vehicule" name="transport_vehicle_id">
                        <option value="">Attribue depuis la route</option>
                        @foreach(($vehicles ?? collect()) as $vehicle)
                            <option value="{{ $vehicle->id }}" @selected((string) old('transport_vehicle_id', $transportAssignment?->vehicle_id) === (string) $vehicle->id)>
                                {{ $vehicle->registration_number ?? $vehicle->plate_number ?? ('Vehicule #' . $vehicle->id) }}
                            </option>
                        @endforeach
                    </x-ui.select>

                    <x-ui.select label="Periode" name="transport_period">
                        <option value="both" @selected(old('transport_period', $transportAssignment?->period ?? 'both') === 'both')>Matin et soir</option>
                        <option value="morning" @selected(old('transport_period', $transportAssignment?->period) === 'morning')>Matin</option>
                        <option value="evening" @selected(old('transport_period', $transportAssignment?->period) === 'evening')>Soir</option>
                    </x-ui.select>

                    <x-ui.input label="Point de ramassage" name="transport_pickup_point" :value="old('transport_pickup_point', $transportAssignment?->pickup_point)" />
                </div>
            </div>
        </x-ui.card>
    </div>

    <x-ui.card title="Frais" subtitle="Configuration financiere de l'eleve.">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.input label="Scolarite mensuelle" name="tuition_monthly" type="number" step="0.01" class="fee-input" :value="old('tuition_monthly', $fee?->tuition_monthly ?? 0)" required />
            <x-ui.input label="Cantine mensuelle" name="canteen_monthly" type="number" step="0.01" class="fee-input" :value="old('canteen_monthly', $fee?->canteen_monthly ?? 0)" />
            <x-ui.input label="Transport mensuel" name="transport_monthly" type="number" step="0.01" class="fee-input" :value="old('transport_monthly', $fee?->transport_monthly ?? 0)" />
            <x-ui.input label="Assurance annuelle" name="insurance_yearly" type="number" step="0.01" :value="old('insurance_yearly', $fee?->insurance_yearly ?? 0)" />
        </div>

        <div class="mt-4 flex flex-wrap items-center justify-between gap-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
            <div class="flex items-center gap-2">
                <input type="hidden" name="insurance_paid" value="0">
                <input id="insurance_paid" type="checkbox" name="insurance_paid" value="1" class="rounded border-slate-300 text-sky-700" {{ old('insurance_paid', $fee?->insurance_paid ?? false) ? 'checked' : '' }}>
                <label for="insurance_paid" class="text-sm font-semibold text-slate-700">Assurance deja payee</label>
            </div>

            <div class="text-right">
                <p class="text-xs text-slate-500">Total mensuel hors assurance</p>
                <p id="totalDisplay" class="text-xl font-bold text-slate-900">0.00 MAD</p>
            </div>
        </div>
    </x-ui.card>

    <div class="app-form-actions">
        <p class="app-form-actions-copy">Verifiez les informations principales, le parent lie et les options de transport avant validation.</p>
        <div class="flex items-center justify-end gap-2">
            <x-ui.button :href="route('admin.students.index')" variant="secondary">Annuler</x-ui.button>
            <x-ui.button type="submit" variant="primary">{{ $isEdit ? 'Enregistrer les modifications' : 'Creer l\'eleve' }}</x-ui.button>
        </div>
    </div>

    <div class="pointer-events-none fixed bottom-6 right-6 z-40 hidden lg:block">
        <x-ui.button type="submit" form="studentForm" variant="primary" class="pointer-events-auto shadow-lg">
            {{ $isEdit ? 'Enregistrer' : 'Creer' }}
        </x-ui.button>
    </div>
</form>

<script>
    (function () {
        const totalDisplay = document.getElementById('totalDisplay');
        if (!totalDisplay) return;

        const calculateTotal = () => {
            let total = 0;
            document.querySelectorAll('.fee-input').forEach((input) => {
                total += parseFloat(input.value || 0);
            });
            totalDisplay.textContent = total.toFixed(2) + ' MAD';
        };

        document.querySelectorAll('.fee-input').forEach((input) => input.addEventListener('input', calculateTotal));
        calculateTotal();
    })();

    (function () {
        const input = document.getElementById('parentSearch');
        const results = document.getElementById('parentResults');
        const hidden = document.getElementById('parentId');
        if (!input || !results || !hidden) return;

        let timer = null;
        const hideResults = () => {
            results.classList.add('hidden');
            results.innerHTML = '';
        };
        const showResults = () => results.classList.remove('hidden');
        const escapeHtml = (str) => (str ?? '').replace(/[&<>\"']/g, (m) => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#039;' }[m]));

        input.addEventListener('input', () => {
            clearTimeout(timer);
            const query = input.value.trim();
            hidden.value = '';
            if (query.length < 2) {
                hideResults();
                return;
            }

            timer = setTimeout(async () => {
                try {
                    const response = await fetch(`{{ route('admin.users.suggest_parents') }}?q=${encodeURIComponent(query)}`);
                    const data = await response.json();
                    if (!Array.isArray(data) || !data.length) {
                        hideResults();
                        return;
                    }

                    results.innerHTML = data.map((parent) => `
                        <button type="button" class="w-full border-b border-slate-100 px-4 py-3 text-left last:border-b-0 hover:bg-slate-50" data-id="${parent.id}" data-label="${escapeHtml(parent.label)}">
                            <div class="text-sm font-semibold text-slate-900">${escapeHtml(parent.label)}</div>
                            ${parent.meta ? `<div class="text-xs text-slate-500">${escapeHtml(parent.meta)}</div>` : ''}
                        </button>
                    `).join('');
                    showResults();
                } catch (error) {
                    hideResults();
                }
            }, 220);
        });

        results.addEventListener('click', (event) => {
            const button = event.target.closest('button[data-id]');
            if (!button) return;
            input.value = button.dataset.label;
            hidden.value = button.dataset.id;
            hideResults();
        });

        document.addEventListener('click', (event) => {
            if (!event.target.closest('#parentResults') && event.target !== input) {
                hideResults();
            }
        });
    })();
</script>
