<x-admin-layout title="Finance">
    @php
        $month = $month ?? now()->format('Y-m');
        $q = $q ?? request('q', '');
        $levelId = $levelId ?? (int) request('level_id', 0);
        $parentId = $parentId ?? (int) request('parent_id', 0);
        $classroomId = $classroomId ?? (int) request('classroom_id', 0);
        $dateFromValue = $dateFrom?->format('Y-m-d') ?? request('date_from');
        $dateToValue = $dateTo?->format('Y-m-d') ?? request('date_to');

        $thisMonthRevenue = $thisMonthRevenue ?? 0;
        $unpaidThisMonth = is_array($unpaidThisMonth ?? null) ? $unpaidThisMonth : [];
        $arrears = is_array($arrears ?? null) ? $arrears : [];
        $unpaidByMonth = is_array($unpaidByMonth ?? null) ? $unpaidByMonth : [];
        $recentPayments = $recentPayments ?? null;
        $maxUnpaid = collect($unpaidByMonth)->max('count') ?: 1;
        $monthLabel = preg_match('/^\d{4}-\d{2}$/', $month)
            ? \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->translatedFormat('F Y')
            : $month;

        $levels = $levels ?? collect([]);
    @endphp

    <x-ui.page-header
        title="Suivi financier"
        subtitle="Paiements, recus, filtres operationnels et historique parent sur un seul ecran."
    >
        <x-slot name="actions">
            <x-ui.button :href="route('admin.finance.payments.create')" variant="primary">
                Ajouter un paiement
            </x-ui.button>
            <x-ui.button :href="route('admin.finance.reminders.edit')" variant="outline">
                Rappels automatiques
            </x-ui.button>
            <x-ui.button :href="route('admin.students.index')" variant="secondary">
                Voir les eleves
            </x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <section class="grid gap-4 md:grid-cols-3">
        <div class="app-stat-card">
            <p class="app-stat-label">Revenu du mois</p>
            <p class="app-stat-value">{{ number_format((float) $thisMonthRevenue, 2) }}</p>
            <p class="app-stat-meta">MAD pour {{ $monthLabel }}</p>
        </div>

        <div class="app-stat-card">
            <p class="app-stat-label">Impayes du mois</p>
            <p class="app-stat-value">{{ count($unpaidThisMonth) }}</p>
            <p class="app-stat-meta">Eleves sans reglement sur la periode courante</p>
        </div>

        <div class="app-stat-card">
            <p class="app-stat-label">Arrieres</p>
            <p class="app-stat-value">{{ count($arrears) }}</p>
            <p class="app-stat-meta">Eleves avec des mois precedents non soldes</p>
        </div>
    </section>

    @if($levels->count())
        <section class="app-card px-5 py-5">
            <div class="flex flex-wrap items-center gap-2">
                <x-ui.button
                    :href="route('admin.finance.index', array_filter(['month' => $month, 'q' => $q, 'parent_id' => $parentId ?: null, 'classroom_id' => $classroomId ?: null, 'date_from' => $dateFromValue, 'date_to' => $dateToValue]))"
                    :variant="$levelId ? 'ghost' : 'outline'"
                    size="sm"
                >
                    Tous les niveaux
                </x-ui.button>

                @foreach($levels as $level)
                    <x-ui.button
                        :href="route('admin.finance.index', array_filter(['month' => $month, 'q' => $q, 'parent_id' => $parentId ?: null, 'classroom_id' => $classroomId ?: null, 'date_from' => $dateFromValue, 'date_to' => $dateToValue, 'level_id' => $level->id]))"
                        :variant="$levelId === (int) $level->id ? 'outline' : 'ghost'"
                        size="sm"
                    >
                        {{ $level->name }}
                    </x-ui.button>
                @endforeach
            </div>
        </section>
    @endif

    <x-ui.card title="Filtres" subtitle="Affinez l'historique par periode, parent, classe ou recherche libre.">
        <form method="GET" action="{{ route('admin.finance.index') }}" class="grid gap-4 xl:grid-cols-[minmax(0,1.3fr)_220px_220px_220px_180px]">
            <div class="app-field relative xl:col-span-2">
                <label for="searchBox" class="app-label">Recherche</label>
                <input type="hidden" name="pick_type" id="pick_type" value="{{ request('pick_type', '') }}">
                <input type="hidden" name="pick_id" id="pick_id" value="{{ request('pick_id', '') }}">

                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="7"></circle>
                            <path d="M21 21l-4.3-4.3"></path>
                        </svg>
                    </span>

                    <input
                        id="searchBox"
                        name="q"
                        value="{{ $q }}"
                        placeholder="Nom du parent, de l'eleve, email ou numero..."
                        autocomplete="off"
                        class="app-input pl-10"
                    >

                    <div id="suggestBox" class="absolute z-20 mt-2 hidden w-full overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-lg"></div>
                </div>

                <p class="app-hint">Choisissez une suggestion pour preparer rapidement un releve individuel.</p>
            </div>

            <div class="app-field">
                <label for="monthInput" class="app-label">Mois de pilotage</label>
                <input id="monthInput" type="month" name="month" value="{{ $month }}" class="app-input">
                <p class="app-hint">Utilise pour les stats et le suivi des impayes.</p>
            </div>

            <div class="app-field">
                <label for="parentFilter" class="app-label">Parent</label>
                <select id="parentFilter" name="parent_id" class="app-input">
                    <option value="">Tous les parents</option>
                    @foreach($parents as $parent)
                        <option value="{{ $parent->id }}" @selected($parentId === (int) $parent->id)>{{ $parent->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="app-field">
                <label for="classroomFilter" class="app-label">Classe</label>
                <select id="classroomFilter" name="classroom_id" class="app-input">
                    <option value="">Toutes les classes</option>
                    @foreach($classrooms as $classroom)
                        <option value="{{ $classroom->id }}" @selected($classroomId === (int) $classroom->id)>{{ $classroom->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="app-field">
                <label for="dateFrom" class="app-label">Du</label>
                <input id="dateFrom" type="date" name="date_from" value="{{ $dateFromValue }}" class="app-input">
            </div>

            <div class="app-field">
                <label for="dateTo" class="app-label">Au</label>
                <input id="dateTo" type="date" name="date_to" value="{{ $dateToValue }}" class="app-input">
            </div>

            @if($levelId)
                <input type="hidden" name="level_id" value="{{ $levelId }}">
            @endif

            <div class="flex flex-col justify-end gap-3 sm:flex-row xl:col-span-2 xl:flex-row">
                <x-ui.button type="submit" variant="primary">
                    Filtrer
                </x-ui.button>

                <x-ui.button :href="route('admin.finance.index')" variant="secondary">
                    Reinitialiser
                </x-ui.button>

                <a
                    id="btnPrintStatement"
                    href="#"
                    target="_blank"
                    class="app-button-outline"
                >
                    Releve PDF
                </a>
            </div>
        </form>
    </x-ui.card>

    @if($selectedParent)
        <x-ui.card title="Historique parent" :subtitle="'Vue rapide pour ' . $selectedParent->name">
            <div class="grid gap-4 lg:grid-cols-[220px_220px_minmax(0,1fr)]">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Total encaisse</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-950">{{ number_format((float) $parentHistoryTotal, 2) }} MAD</p>
                    <p class="mt-1 text-sm text-slate-500">Sur l'ensemble de l'historique du parent.</p>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Dernier paiement</p>
                    <p class="mt-2 text-lg font-semibold text-slate-950">{{ $parentHistoryLastPaidAt?->format('d/m/Y H:i') ?? '-' }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ $selectedParent->email ?: 'Aucun email renseigne' }}</p>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <p class="text-sm font-semibold text-slate-900">Derniers reglements</p>
                        <a
                            href="{{ route('admin.finance.statement.print', ['type' => 'parent', 'id' => $selectedParent->id, 'month' => $month]) }}"
                            target="_blank"
                            class="text-xs font-semibold text-sky-700 hover:text-sky-800"
                        >
                            Imprimer le releve
                        </a>
                    </div>

                    <div class="space-y-3">
                        @forelse($parentHistoryPayments as $payment)
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="font-semibold text-slate-900">{{ $payment->student?->full_name ?? '-' }}</p>
                                        <p class="mt-1 text-sm text-slate-500">
                                            {{ $payment->student?->classroom?->name ?? '-' }}
                                            <span class="mx-1 text-slate-300">|</span>
                                            {{ $payment->paid_at?->format('d/m/Y H:i') ?? '-' }}
                                        </p>
                                    </div>
                                    <p class="text-sm font-semibold text-slate-900">{{ number_format((float) $payment->amount, 2) }} MAD</p>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-sm text-slate-500">
                                Aucun paiement enregistre pour ce parent.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </x-ui.card>
    @endif

    <x-ui.card title="Impayes sur 12 mois" subtitle="Cliquez sur une barre pour filtrer l'ecran sur le mois correspondant.">
        <div class="overflow-x-auto">
            <div class="min-w-[900px]">
                <div class="flex h-56 items-end gap-3">
                    @forelse($unpaidByMonth as $item)
                        @php
                            $count = (int) ($item['count'] ?? 0);
                            $height = max(8, (int) round(($count / $maxUnpaid) * 180));
                        @endphp
                        <a
                            href="{{ route('admin.finance.index', array_filter(['month' => $item['ym'] ?? $month, 'q' => $q, 'level_id' => $levelId ?: null, 'parent_id' => $parentId ?: null, 'classroom_id' => $classroomId ?: null, 'date_from' => $dateFromValue, 'date_to' => $dateToValue])) }}"
                            class="group flex-1"
                        >
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-2 transition group-hover:border-sky-200 group-hover:bg-sky-50">
                                <div class="rounded-xl bg-sky-700/85 transition group-hover:bg-sky-700" style="height: {{ $height }}px;"></div>
                            </div>
                            <div class="mt-2 text-center text-[11px] text-slate-500">
                                {{ $item['label'] ?? '' }}
                                <span class="font-semibold text-slate-900">| {{ $count }}</span>
                            </div>
                        </a>
                    @empty
                        <div class="py-10 text-sm text-slate-500">Aucune donnee disponible.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </x-ui.card>

    <section class="grid gap-6 lg:grid-cols-2">
        <x-ui.card title="Impayes du mois" :subtitle="'Periode : '.$month">
            <div class="space-y-3">
                @forelse($unpaidThisMonth as $item)
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold text-slate-900">{{ $item['student'] ?? 'Eleve non renseigne' }}</p>
                                <p class="mt-1 text-sm text-slate-500">
                                    {{ $item['parent'] ?? 'Parent non renseigne' }}
                                    <span class="mx-1 text-slate-300">|</span>
                                    {{ $item['classroom'] ?? 'Classe non renseignee' }}
                                </p>
                            </div>

                            <p class="text-sm font-semibold text-slate-900">{{ number_format((float) ($item['monthly_total'] ?? 0), 2) }} MAD</p>
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-sm text-slate-500">
                        Aucun impaye detecte pour ce mois.
                    </div>
                @endforelse
            </div>
        </x-ui.card>

        <x-ui.card title="Arrieres" subtitle="Mois anterieurs non regles">
            <div class="space-y-3">
                @forelse($arrears as $item)
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold text-slate-900">{{ $item['student'] ?? 'Eleve non renseigne' }}</p>
                                <p class="mt-1 text-sm text-slate-500">
                                    {{ $item['parent'] ?? 'Parent non renseigne' }}
                                    <span class="mx-1 text-slate-300">|</span>
                                    {{ $item['classroom'] ?? 'Classe non renseignee' }}
                                </p>
                            </div>

                            <x-ui.badge variant="warning">{{ (int) ($item['missing_count'] ?? 0) }} mois</x-ui.badge>
                        </div>

                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach(array_slice(($item['missing_months'] ?? []), 0, 8) as $missingMonth)
                                <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-700">
                                    {{ $missingMonth }}
                                </span>
                            @endforeach

                            @if(count(($item['missing_months'] ?? [])) > 8)
                                <span class="text-xs text-slate-500">+{{ count($item['missing_months']) - 8 }} autres</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-sm text-slate-500">
                        Aucun arriere en cours.
                    </div>
                @endforelse
            </div>
        </x-ui.card>
    </section>

    <x-ui.card title="Historique des paiements" subtitle="Liste filtree selon la recherche, la periode, le parent et la classe.">
        <div class="mb-4 flex items-center justify-between gap-3">
            <p class="app-hint">
                Resultats : <span class="font-semibold text-slate-900">{{ $recentPayments?->total() ?? 0 }}</span>
                @if($dateFromValue || $dateToValue)
                    <span class="ml-2 text-slate-400">|</span>
                    <span class="ml-2">Periode personnalisee</span>
                @endif
            </p>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200">
            <div class="overflow-x-auto">
                <table class="app-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Parent</th>
                            <th>Eleve</th>
                            <th>Classe</th>
                            <th>Montant</th>
                            <th>Methode</th>
                            <th>Recu</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($recentPayments)
                            @forelse($recentPayments as $payment)
                                <tr>
                                    <td>{{ $payment->paid_at?->format('Y-m-d H:i') ?? '-' }}</td>
                                    <td class="font-semibold text-slate-900">{{ $payment->receipt?->parent?->name ?? $payment->student?->parentUser?->name ?? '-' }}</td>
                                    <td class="font-semibold text-slate-900">{{ $payment->student?->full_name ?? '-' }}</td>
                                    <td>{{ $payment->student?->classroom?->name ?? '-' }}</td>
                                    <td>{{ number_format((float) ($payment->amount ?? 0), 2) }} MAD</td>
                                    <td>{{ strtoupper((string) ($payment->method ?? '-')) }}</td>
                                    <td>
                                        @if($payment->receipt)
                                            <a
                                                class="font-semibold text-sky-700 hover:text-sky-800 hover:underline"
                                                href="{{ route('admin.finance.receipts.show', $payment->receipt) }}"
                                            >
                                                {{ $payment->receipt->receipt_number ?? 'Recu' }}
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <div class="flex justify-end gap-2">
                                            <x-ui.button
                                                :href="route('admin.finance.statement.print').'?type=student&id='.$payment->student_id.'&month='.$month"
                                                variant="ghost"
                                                size="sm"
                                                target="_blank"
                                            >
                                                Releve
                                            </x-ui.button>
                                            @if($payment->receipt)
                                                <x-ui.button
                                                    :href="route('admin.finance.receipts.export', $payment->receipt)"
                                                    variant="ghost"
                                                    size="sm"
                                                    target="_blank"
                                                >
                                                    PDF
                                                </x-ui.button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-5 py-8 text-center text-sm text-slate-500">Aucun paiement trouve pour ces filtres.</td>
                                </tr>
                            @endforelse
                        @else
                            <tr>
                                <td colspan="8" class="px-5 py-8 text-center text-sm text-slate-500">Aucun paiement disponible.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $recentPayments?->links() }}
        </div>
    </x-ui.card>

    <script>
        (function () {
            const search = document.getElementById('searchBox');
            const box = document.getElementById('suggestBox');
            const pickType = document.getElementById('pick_type');
            const pickId = document.getElementById('pick_id');
            const monthInput = document.getElementById('monthInput');
            const btnPrint = document.getElementById('btnPrintStatement');
            let timer = null;

            function hideBox() {
                if (!box) return;
                box.classList.add('hidden');
                box.innerHTML = '';
            }

            function showBox() {
                if (!box) return;
                box.classList.remove('hidden');
            }

            async function fetchSuggest(query) {
                const url = "{{ route('admin.finance.suggest', [], false) }}" + "?q=" + encodeURIComponent(query);
                const response = await fetch(url, { headers: { Accept: 'application/json' } });
                return await response.json();
            }

            function renderSuggest(items) {
                if (!box) return;

                if (!items.length) {
                    box.innerHTML = '<div class="p-3 text-sm text-slate-500">Aucun resultat.</div>';
                    showBox();
                    return;
                }

                box.innerHTML = items.map((item) => `
                    <button
                        type="button"
                        class="flex w-full items-start justify-between gap-3 px-4 py-3 text-left hover:bg-slate-50"
                        data-type="${item.type}"
                        data-id="${item.id}"
                        data-label="${item.label}"
                    >
                        <div>
                            <div class="text-sm font-semibold text-slate-900">${item.label}</div>
                            <div class="text-xs text-slate-500">${item.meta ?? ''}</div>
                        </div>
                        <span class="text-[10px] uppercase tracking-[0.2em] text-slate-400">${item.type}</span>
                    </button>
                `).join('');

                showBox();

                box.querySelectorAll('button').forEach((button) => {
                    button.addEventListener('click', () => {
                        search.value = button.dataset.label;
                        pickType.value = button.dataset.type;
                        pickId.value = button.dataset.id;
                        hideBox();
                        refreshPrintLink();
                    });
                });
            }

            function refreshPrintLink() {
                if (!btnPrint || !pickType || !pickId || !monthInput) return;

                const type = pickType.value;
                const id = pickId.value;
                const monthValue = monthInput.value;

                if (!type || !id) {
                    btnPrint.href = '#';
                    btnPrint.classList.add('opacity-50', 'pointer-events-none');
                    return;
                }

                btnPrint.classList.remove('opacity-50', 'pointer-events-none');
                btnPrint.href = "{{ route('admin.finance.statement.print') }}"
                    + `?type=${encodeURIComponent(type)}&id=${encodeURIComponent(id)}&month=${encodeURIComponent(monthValue)}`;
            }

            if (search) {
                search.addEventListener('input', () => {
                    pickType.value = '';
                    pickId.value = '';
                    refreshPrintLink();

                    const query = search.value.trim();
                    if (query.length < 2) {
                        hideBox();
                        return;
                    }

                    clearTimeout(timer);
                    timer = setTimeout(async () => {
                        try {
                            const items = await fetchSuggest(query);
                            renderSuggest(items);
                        } catch (error) {
                            hideBox();
                        }
                    }, 180);
                });
            }

            document.addEventListener('click', (event) => {
                if (box && !box.contains(event.target) && event.target !== search) {
                    hideBox();
                }
            });

            if (monthInput) {
                monthInput.addEventListener('change', refreshPrintLink);
            }

            refreshPrintLink();
        })();
    </script>
</x-admin-layout>
