<x-admin-layout title="Ajouter un paiement">
    @php
        $oldStudentIds = collect(old('student_ids', []))->map(fn ($id) => (int) $id)->values();
        $oldMonths = collect(old('months', []))->map(fn ($month) => (string) $month)->values();
        $oldParentId = (int) old('parent_id', 0);
        $oldParent = $oldParentId > 0 ? $parents->firstWhere('id', $oldParentId) : null;
    @endphp

    <x-ui.page-header
        title="Ajouter un paiement"
        subtitle="Selectionnez un parent, verifiez les eleves lies et enregistrez le reglement sur des mois precis."
    >
        <x-slot name="actions">
            <x-ui.button :href="route('admin.finance.index')" variant="secondary">
                Retour a la finance
            </x-ui.button>
        </x-slot>
    </x-ui.page-header>

    @if(session('warning'))
        <x-ui.alert variant="warning">{!! session('warning') !!}</x-ui.alert>
    @endif

    @if($errors->any())
        <x-ui.alert variant="error">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-ui.alert>
    @endif

    <form method="POST" action="{{ route('admin.finance.payments.store') }}" class="space-y-6" id="financePaymentForm">
        @csrf

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.45fr)_380px]">
            <div class="space-y-6">
                <x-ui.card title="1. Parent et eleves" subtitle="Le chargement des eleves et des frais se fait automatiquement depuis l'ecole active.">
                    <div class="grid gap-4 lg:grid-cols-[minmax(0,0.92fr)_minmax(0,1.08fr)]">
                        <x-ui.input
                            id="parent_search"
                            label="Recherche rapide"
                            placeholder="Nom, email ou telephone"
                            hint="Filtre localement la liste des parents de cette ecole."
                        />

                        <div class="relative">
                            <x-ui.select
                                id="parent_id"
                                name="parent_id"
                                label="Parent"
                                data-students-url-template="{{ route('admin.parents.students', ['parent' => '__PARENT__'], false) }}"
                            >
                                <option value="">Choisir un parent</option>
                                @foreach($parents as $parent)
                                    <option value="{{ $parent->id }}" @selected($oldParentId === (int) $parent->id)>
                                        {{ $parent->name }}@if($parent->email) ({{ $parent->email }})@endif
                                    </option>
                                @endforeach
                            </x-ui.select>

                            <div id="parentLoading" class="absolute right-3 top-10 hidden text-xs font-medium text-slate-500">
                                Chargement...
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-4 lg:grid-cols-[minmax(0,1fr)_auto]">
                        <div id="selectedParentBox" class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <p class="text-[0.72rem] font-semibold uppercase tracking-[0.16em] text-slate-500">Parent selectionne</p>
                            <p id="selectedParentName" class="mt-2 text-sm font-semibold text-slate-900">
                                {{ $oldParent?->name ?? 'Aucun parent selectionne' }}
                            </p>
                            <p id="selectedParentMeta" class="mt-1 text-xs text-slate-500">
                                {{ $oldParent?->email ?: 'Choisissez un parent pour charger ses eleves et leurs frais.' }}
                            </p>
                        </div>

                        <div id="kidsInfo" class="hidden rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                            Eleves trouves : <span id="kidsCount" class="font-semibold">0</span>
                        </div>
                    </div>

                    <div id="debugBox" class="mt-4 hidden rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        <p class="font-semibold">Informations indisponibles</p>
                        <p id="debugText" class="mt-1 text-xs text-rose-600"></p>
                    </div>

                    <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">Eleves concernes</p>
                            <p class="text-xs text-slate-500">Seuls les eleves lies au parent et appartenant a l'ecole active sont affiches.</p>
                        </div>

                        <div id="studentsToolbar" class="hidden flex-wrap gap-2">
                            <x-ui.button type="button" id="selectAllStudentsBtn" variant="secondary" size="sm">
                                Tout selectionner
                            </x-ui.button>
                            <x-ui.button type="button" id="clearStudentsBtn" variant="ghost" size="sm">
                                Effacer
                            </x-ui.button>
                        </div>
                    </div>

                    <div id="studentsBox" class="mt-4 space-y-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-8 text-center text-sm text-slate-500">
                            Choisissez d'abord un parent.
                        </div>
                    </div>

                    <div id="studentsHint" class="mt-3 hidden text-xs font-medium text-rose-600">
                        Selectionnez au moins un eleve avec des frais mensuels.
                    </div>
                </x-ui.card>

                <x-ui.card title="2. Periode a regler" subtitle="Generez les mois, puis ajustez la selection avant validation.">
                    <div class="grid gap-4 md:grid-cols-2">
                        <x-ui.input id="month_start" type="month" label="Du mois" :value="old('month_start', $oldMonths->first())" />
                        <x-ui.input id="month_end" type="month" label="Au mois" :value="old('month_end', $oldMonths->last())" />
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <x-ui.button type="button" id="genMonthsBtn" variant="primary" size="sm">
                            Generer les mois
                        </x-ui.button>
                        <x-ui.button type="button" id="selectAllMonthsBtn" variant="secondary" size="sm">
                            Tout selectionner
                        </x-ui.button>
                        <x-ui.button type="button" id="clearMonthsBtn" variant="ghost" size="sm">
                            Vider
                        </x-ui.button>
                    </div>

                    <div id="monthsBox" class="mt-4 flex min-h-[60px] flex-wrap gap-2 rounded-2xl border border-slate-200 bg-slate-50 p-3">
                        <div class="text-sm text-slate-500">Generez d'abord les mois a regler.</div>
                    </div>

                    <div id="monthsHint" class="mt-3 hidden text-xs font-medium text-rose-600">
                        Selectionnez au moins un mois.
                    </div>
                </x-ui.card>

                <x-ui.card title="3. Informations de paiement" subtitle="Ces informations seront reprises sur le recu.">
                    <div class="grid gap-4 md:grid-cols-2">
                        <x-ui.select name="method" label="Methode">
                            <option value="cash" @selected(old('method', 'cash') === 'cash')>Especes</option>
                            <option value="transfer" @selected(old('method') === 'transfer')>Virement</option>
                            <option value="card" @selected(old('method') === 'card')>Carte</option>
                            <option value="check" @selected(old('method') === 'check')>Cheque</option>
                        </x-ui.select>

                        <x-ui.input name="paid_at" type="datetime-local" label="Paye le (optionnel)" :value="old('paid_at')" />
                    </div>

                    <div class="mt-4">
                        <x-ui.input name="note" label="Note (optionnel)" :value="old('note')" hint="Visible sur le recu si vous la renseignez." />
                    </div>
                </x-ui.card>
            </div>

            <div class="space-y-6">
                <x-ui.card title="Resume" subtitle="Controle final avant creation du recu." class="xl:sticky xl:top-24">
                    <div class="space-y-4">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Parent</p>
                            <p id="summaryParent" class="mt-2 text-lg font-semibold text-slate-900">
                                {{ $oldParent?->name ?? 'Aucun parent' }}
                            </p>
                            <p id="summaryParentMeta" class="mt-1 text-xs text-slate-500">
                                {{ $oldParent?->email ?: 'Selectionnez un parent pour afficher son resume.' }}
                            </p>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Total selectionne</p>
                            <p id="totalDisplay" class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">0.00 MAD</p>
                            <div class="mt-3 grid gap-2 text-sm text-slate-600">
                                <div class="flex items-center justify-between gap-3">
                                    <span>Eleves choisis</span>
                                    <span id="studentsCount" class="font-semibold text-slate-900">0</span>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span>Mois choisis</span>
                                    <span id="monthsCount" class="font-semibold text-slate-900">0</span>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span>Sous-total mensuel</span>
                                    <span id="monthlySubtotal" class="font-semibold text-slate-900">0.00 MAD</span>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Elements selectionnes</p>
                            <div id="selectionPreview" class="mt-3 space-y-2 text-sm text-slate-600">
                                <p>Aucune selection en cours.</p>
                            </div>
                        </div>
                    </div>
                </x-ui.card>

                <div class="flex flex-col gap-3">
                    <x-ui.button id="submitBtn" type="submit" variant="primary">
                        Enregistrer et generer le recu
                    </x-ui.button>
                    <x-ui.button :href="route('admin.finance.index')" variant="secondary">
                        Annuler
                    </x-ui.button>
                </div>
            </div>
        </div>
    </form>

    <script>
        (function () {
            const form = document.getElementById('financePaymentForm');
            const parentSearch = document.getElementById('parent_search');
            const parentSelect = document.getElementById('parent_id');
            const parentLoading = document.getElementById('parentLoading');
            const selectedParentName = document.getElementById('selectedParentName');
            const selectedParentMeta = document.getElementById('selectedParentMeta');
            const summaryParent = document.getElementById('summaryParent');
            const summaryParentMeta = document.getElementById('summaryParentMeta');
            const kidsInfo = document.getElementById('kidsInfo');
            const kidsCount = document.getElementById('kidsCount');
            const debugBox = document.getElementById('debugBox');
            const debugText = document.getElementById('debugText');
            const studentsToolbar = document.getElementById('studentsToolbar');
            const selectAllStudentsBtn = document.getElementById('selectAllStudentsBtn');
            const clearStudentsBtn = document.getElementById('clearStudentsBtn');
            const studentsBox = document.getElementById('studentsBox');
            const studentsHint = document.getElementById('studentsHint');
            const monthStart = document.getElementById('month_start');
            const monthEnd = document.getElementById('month_end');
            const genMonthsBtn = document.getElementById('genMonthsBtn');
            const selectAllMonthsBtn = document.getElementById('selectAllMonthsBtn');
            const clearMonthsBtn = document.getElementById('clearMonthsBtn');
            const monthsBox = document.getElementById('monthsBox');
            const monthsHint = document.getElementById('monthsHint');
            const totalDisplay = document.getElementById('totalDisplay');
            const studentsCountEl = document.getElementById('studentsCount');
            const monthsCountEl = document.getElementById('monthsCount');
            const monthlySubtotalEl = document.getElementById('monthlySubtotal');
            const selectionPreview = document.getElementById('selectionPreview');
            const submitBtn = document.getElementById('submitBtn');
            const studentsUrlTemplate = parentSelect?.dataset.studentsUrlTemplate || '';

            if (!form || !parentSelect || !studentsBox || !monthsBox || !submitBtn || !studentsUrlTemplate) {
                return;
            }

            const serverSelectedStudentIds = new Set(@json($oldStudentIds->all()).map(Number));
            const oldMonths = @json($oldMonths->all());
            const allParentOptions = Array.from(parentSelect.options).map((option) => ({
                value: option.value,
                text: option.textContent,
            }));

            const state = {
                abortController: null,
                requestId: 0,
                selectedStudentIds: new Set(serverSelectedStudentIds),
                studentsById: new Map(),
            };

            function escapeHtml(value) {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function formatMoney(value) {
                return `${Number(value || 0).toFixed(2)} MAD`;
            }

            function pad2(value) {
                return String(value).padStart(2, '0');
            }

            function sourceBadgeClass(source) {
                switch (source) {
                    case 'parent_student_fee':
                        return 'border-emerald-200 bg-emerald-50 text-emerald-700';
                    case 'student_fee_plan':
                        return 'border-sky-200 bg-sky-50 text-sky-700';
                    case 'classroom_fee':
                        return 'border-amber-200 bg-amber-50 text-amber-700';
                    default:
                        return 'border-slate-200 bg-slate-100 text-slate-600';
                }
            }

            function monthsBetween(startYM, endYM) {
                const [startYear, startMonth] = startYM.split('-').map(Number);
                const [endYear, endMonth] = endYM.split('-').map(Number);
                const output = [];

                let year = startYear;
                let month = startMonth;

                while (year < endYear || (year === endYear && month <= endMonth)) {
                    output.push(`${year}-${pad2(month)}`);
                    month += 1;
                    if (month === 13) {
                        month = 1;
                        year += 1;
                    }
                }

                return output;
            }

            function getParentOption(parentId) {
                return Array.from(parentSelect.options).find((option) => option.value === String(parentId));
            }

            function updateParentSummary() {
                const option = getParentOption(parentSelect.value);
                const label = option?.textContent?.trim() || 'Aucun parent selectionne';

                selectedParentName.textContent = label;
                summaryParent.textContent = option ? label : 'Aucun parent';

                if (option && parentSelect.value) {
                    const meta = label.includes('(') ? label.slice(label.indexOf('(')).replace(/[()]/g, '').trim() : 'Parent de cette ecole';
                    selectedParentMeta.textContent = meta || 'Parent de cette ecole';
                    summaryParentMeta.textContent = meta || 'Parent de cette ecole';
                } else {
                    selectedParentMeta.textContent = 'Choisissez un parent pour charger ses eleves et leurs frais.';
                    summaryParentMeta.textContent = 'Selectionnez un parent pour afficher son resume.';
                }
            }

            function hideDebug() {
                debugText.textContent = '';
                debugBox.classList.add('hidden');
            }

            function showDebug(message) {
                debugText.textContent = message || 'Une erreur est survenue. Veuillez reessayer.';
                debugBox.classList.remove('hidden');
            }

            function setStudentsPlaceholder(message, variant = 'muted') {
                const toneClass = variant === 'error'
                    ? 'border-rose-200 bg-rose-50 text-rose-700'
                    : 'border-slate-300 bg-white text-slate-500';

                studentsToolbar.classList.add('hidden');
                studentsBox.innerHTML = `
                    <div class="rounded-2xl border border-dashed px-4 py-8 text-center text-sm ${toneClass}">
                        ${escapeHtml(message)}
                    </div>
                `;
            }

            function getSelectedMonths() {
                return Array.from(monthsBox.querySelectorAll('.month-check:checked')).map((checkbox) => checkbox.value);
            }

            function getSelectedStudentEntries() {
                return Array.from(state.selectedStudentIds)
                    .map((studentId) => state.studentsById.get(Number(studentId)))
                    .filter(Boolean)
                    .filter((student) => Number(student.monthly_total || 0) > 0);
            }

            function updateSubmitState() {
                const hasParent = parentSelect.value !== '';
                const hasStudents = getSelectedStudentEntries().length > 0;
                const hasMonths = getSelectedMonths().length > 0;
                const disabled = !(hasParent && hasStudents && hasMonths);

                submitBtn.disabled = disabled;
                submitBtn.classList.toggle('opacity-60', disabled);
                submitBtn.classList.toggle('cursor-not-allowed', disabled);
            }

            function updateSummary() {
                const selectedStudents = getSelectedStudentEntries();
                const selectedMonths = getSelectedMonths();
                const monthlySubtotal = selectedStudents.reduce((total, student) => total + Number(student.monthly_total || 0), 0);
                const grandTotal = monthlySubtotal * selectedMonths.length;

                totalDisplay.textContent = formatMoney(grandTotal);
                monthlySubtotalEl.textContent = formatMoney(monthlySubtotal);
                studentsCountEl.textContent = String(selectedStudents.length);
                monthsCountEl.textContent = String(selectedMonths.length);

                if (!selectedStudents.length && !selectedMonths.length) {
                    selectionPreview.innerHTML = '<p>Aucune selection en cours.</p>';
                    updateSubmitState();
                    return;
                }

                const studentLines = selectedStudents.slice(0, 4).map((student) => `
                    <div class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2">
                        <div>
                            <p class="font-semibold text-slate-900">${escapeHtml(student.full_name)}</p>
                            <p class="text-xs text-slate-500">${escapeHtml(student.classroom || 'Classe non renseignee')}</p>
                        </div>
                        <span class="text-xs font-semibold text-slate-900">${formatMoney(student.monthly_total)}</span>
                    </div>
                `).join('');

                const extraStudents = selectedStudents.length > 4
                    ? `<p class="text-xs text-slate-500">+${selectedStudents.length - 4} autre(s) eleve(s)</p>`
                    : '';

                const monthsLine = selectedMonths.length
                    ? `<div class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">Mois : <span class="font-semibold text-slate-900">${escapeHtml(selectedMonths.join(', '))}</span></div>`
                    : '<p class="text-xs text-slate-500">Aucun mois selectionne.</p>';

                selectionPreview.innerHTML = `
                    <div class="space-y-2">
                        ${studentLines || '<p class="text-xs text-slate-500">Aucun eleve selectionne.</p>'}
                        ${extraStudents}
                        ${monthsLine}
                    </div>
                `;

                updateSubmitState();
            }

            function renderStudents(items) {
                state.studentsById = new Map(items.map((student) => [Number(student.id), student]));
                state.selectedStudentIds = new Set(
                    Array.from(state.selectedStudentIds).filter((studentId) => state.studentsById.has(Number(studentId)))
                );

                if (!Array.isArray(items) || items.length === 0) {
                    setStudentsPlaceholder('Aucun eleve lie a ce parent.');
                    updateSummary();
                    return;
                }

                studentsToolbar.classList.remove('hidden');
                studentsBox.innerHTML = items.map((student) => {
                    const id = Number(student.id);
                    const details = student.details || {};
                    const monthly = Number(student.monthly_total || 0);
                    const insurance = Number(details.insurance_yearly || 0);
                    const insurancePaid = Boolean(details.insurance_paid);
                    const hasMonthlyFees = monthly > 0;
                    const checked = hasMonthlyFees && state.selectedStudentIds.has(id);
                    const sourceLabel = student.fee_source_label || 'Aucun tarif';
                    const sourceClass = sourceBadgeClass(student.fee_source);
                    const helperText = hasMonthlyFees
                        ? `Scolarite ${Number(details.tuition || 0).toFixed(2)} MAD | Cantine ${Number(details.canteen || 0).toFixed(2)} MAD | Transport ${Number(details.transport || 0).toFixed(2)} MAD`
                        : 'Aucun frais mensuel disponible pour cet eleve.';
                    const insuranceText = insurance > 0
                        ? `Assurance annuelle ${insurance.toFixed(2)} MAD${insurancePaid ? ' - deja payee' : ''}`
                        : 'Pas d assurance annuelle';

                    return `
                        <label class="flex items-start justify-between gap-4 rounded-2xl border border-slate-200 bg-white px-4 py-4 transition hover:border-slate-300 hover:bg-slate-50 ${hasMonthlyFees ? '' : 'opacity-80'}">
                            <div class="flex min-w-0 items-start gap-3">
                                <input
                                    type="checkbox"
                                    class="student-check mt-1"
                                    name="student_ids[]"
                                    value="${id}"
                                    data-monthly="${monthly}"
                                    ${checked ? 'checked' : ''}
                                    ${hasMonthlyFees ? '' : 'disabled'}
                                >
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="text-sm font-semibold text-slate-900">${escapeHtml(student.full_name)}</p>
                                        <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-semibold ${sourceClass}">
                                            ${escapeHtml(sourceLabel)}
                                        </span>
                                    </div>
                                    <p class="mt-1 text-xs text-slate-500">${escapeHtml(student.classroom || 'Classe non renseignee')}</p>
                                    <p class="mt-2 text-xs text-slate-600">${escapeHtml(helperText)}</p>
                                    <p class="mt-1 text-[11px] text-slate-500">${escapeHtml(insuranceText)}</p>
                                </div>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Mensuel</p>
                                <p class="mt-2 text-sm font-semibold text-slate-900">${formatMoney(monthly)}</p>
                                ${hasMonthlyFees ? '' : '<p class="mt-1 text-[11px] text-amber-600">Non payable sur cette page</p>'}
                            </div>
                        </label>
                    `;
                }).join('');

                updateSummary();
            }

            function renderMonths(months, preferredSelection = []) {
                if (!Array.isArray(months) || months.length === 0) {
                    monthsBox.innerHTML = '<div class="text-sm text-slate-500">Generez d abord les mois a regler.</div>';
                    updateSummary();
                    return;
                }

                const preferred = new Set(preferredSelection);
                monthsBox.innerHTML = months.map((month) => `
                    <label class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">
                        <input
                            type="checkbox"
                            class="month-check"
                            name="months[]"
                            value="${month}"
                            ${preferred.size === 0 || preferred.has(month) ? 'checked' : ''}
                        >
                        <span>${month}</span>
                    </label>
                `).join('');

                updateSummary();
            }

            async function loadStudents(parentId) {
                state.requestId += 1;
                const requestId = state.requestId;

                if (state.abortController) {
                    state.abortController.abort();
                }

                state.abortController = typeof AbortController !== 'undefined' ? new AbortController() : null;

                hideDebug();
                setStudentsPlaceholder('Chargement des eleves...');
                parentLoading.classList.remove('hidden');
                kidsInfo.classList.add('hidden');
                kidsCount.textContent = '0';

                try {
                    const url = studentsUrlTemplate.replace('__PARENT__', parentId);
                    const response = await fetch(url, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                        cache: 'no-store',
                        signal: state.abortController?.signal,
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }

                    const payload = await response.json();
                    if (requestId !== state.requestId) {
                        return;
                    }

                    kidsInfo.classList.remove('hidden');
                    kidsCount.textContent = String(payload.count || 0);
                    renderStudents(Array.isArray(payload.students) ? payload.students : []);
                } catch (error) {
                    if (error?.name === 'AbortError' || requestId !== state.requestId) {
                        return;
                    }

                    setStudentsPlaceholder('Impossible de charger les eleves.', 'error');
                    showDebug(error?.message || 'Connexion temporairement indisponible. Veuillez reessayer.');
                    updateSummary();
                } finally {
                    if (requestId === state.requestId) {
                        parentLoading.classList.add('hidden');
                    }
                }
            }

            function filterParents() {
                if (!parentSearch) {
                    return;
                }

                const term = parentSearch.value.trim().toLowerCase();
                const selectedValue = parentSelect.value;
                let filtered = allParentOptions.filter((option, index) => index === 0 || term.length < 2 || option.text.toLowerCase().includes(term));

                if (selectedValue && !filtered.some((option) => option.value === selectedValue)) {
                    const selectedOption = allParentOptions.find((option) => option.value === selectedValue);
                    if (selectedOption) {
                        filtered = [filtered[0], selectedOption, ...filtered.slice(1)];
                    }
                }

                parentSelect.innerHTML = '';
                filtered.forEach((option) => {
                    const element = document.createElement('option');
                    element.value = option.value;
                    element.textContent = option.text;
                    element.selected = option.value === selectedValue;
                    parentSelect.appendChild(element);
                });
            }

            function resetStudentSelection() {
                state.selectedStudentIds = new Set();
                studentsHint.classList.add('hidden');
            }

            genMonthsBtn?.addEventListener('click', () => {
                const start = monthStart.value;
                const end = monthEnd.value;

                if (!start || !end) {
                    monthsBox.innerHTML = '<div class="text-sm font-medium text-rose-600">Choisissez un mois de debut et un mois de fin.</div>';
                    updateSummary();
                    return;
                }

                if (start > end) {
                    monthsBox.innerHTML = '<div class="text-sm font-medium text-rose-600">Le mois de debut doit etre inferieur ou egal au mois de fin.</div>';
                    updateSummary();
                    return;
                }

                renderMonths(monthsBetween(start, end), getSelectedMonths());
                monthsHint.classList.add('hidden');
            });

            selectAllMonthsBtn?.addEventListener('click', () => {
                monthsBox.querySelectorAll('.month-check').forEach((checkbox) => {
                    checkbox.checked = true;
                });
                monthsHint.classList.add('hidden');
                updateSummary();
            });

            clearMonthsBtn?.addEventListener('click', () => {
                monthsBox.querySelectorAll('.month-check').forEach((checkbox) => {
                    checkbox.checked = false;
                });
                updateSummary();
            });

            selectAllStudentsBtn?.addEventListener('click', () => {
                studentsBox.querySelectorAll('.student-check:not(:disabled)').forEach((checkbox) => {
                    checkbox.checked = true;
                    state.selectedStudentIds.add(Number(checkbox.value));
                });
                studentsHint.classList.add('hidden');
                updateSummary();
            });

            clearStudentsBtn?.addEventListener('click', () => {
                studentsBox.querySelectorAll('.student-check').forEach((checkbox) => {
                    checkbox.checked = false;
                });
                resetStudentSelection();
                updateSummary();
            });

            studentsBox.addEventListener('change', (event) => {
                const target = event.target;
                if (!(target instanceof HTMLInputElement) || !target.classList.contains('student-check')) {
                    return;
                }

                const studentId = Number(target.value);
                if (target.checked) {
                    state.selectedStudentIds.add(studentId);
                } else {
                    state.selectedStudentIds.delete(studentId);
                }

                studentsHint.classList.toggle('hidden', getSelectedStudentEntries().length > 0);
                updateSummary();
            });

            monthsBox.addEventListener('change', (event) => {
                const target = event.target;
                if (!(target instanceof HTMLInputElement) || !target.classList.contains('month-check')) {
                    return;
                }

                monthsHint.classList.toggle('hidden', getSelectedMonths().length > 0);
                updateSummary();
            });

            parentSelect.addEventListener('change', () => {
                updateParentSummary();
                resetStudentSelection();

                if (!parentSelect.value) {
                    kidsInfo.classList.add('hidden');
                    kidsCount.textContent = '0';
                    hideDebug();
                    setStudentsPlaceholder('Choisissez d abord un parent.');
                    updateSummary();
                    return;
                }

                loadStudents(parentSelect.value);
            });

            parentSearch?.addEventListener('input', filterParents);

            form.addEventListener('submit', (event) => {
                const hasParent = parentSelect.value !== '';
                const hasStudents = getSelectedStudentEntries().length > 0;
                const hasMonths = getSelectedMonths().length > 0;

                studentsHint.classList.toggle('hidden', hasStudents);
                monthsHint.classList.toggle('hidden', hasMonths);

                if (!hasParent || !hasStudents || !hasMonths) {
                    event.preventDefault();

                    if (!hasParent) {
                        parentSelect.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    } else if (!hasStudents) {
                        studentsBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    } else {
                        monthsBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
            });

            updateParentSummary();

            if (oldMonths.length > 0) {
                renderMonths(oldMonths, oldMonths);
            } else {
                updateSummary();
            }

            if (parentSelect.value) {
                loadStudents(parentSelect.value);
            } else {
                updateSubmitState();
            }
        })();
    </script>
</x-admin-layout>
