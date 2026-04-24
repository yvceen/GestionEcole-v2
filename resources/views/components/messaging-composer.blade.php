@props([
    'title' => 'Nouveau message',
    'subtitle' => 'Envoyez un message clair et bien ciblé.',
    'action',
    'method' => 'POST',
    'backUrl' => null,
    'backLabel' => 'Retour',
    'submitLabel' => 'Envoyer',
    'subjectValue' => null,
    'bodyValue' => null,
    'tabs' => [],
])

@php
use Illuminate\Support\Js;

$composerTabs = collect($tabs ?? [])->filter(function ($tab) {
    return isset($tab['key'], $tab['items']);
})->map(function ($tab) {
    return [
        'key' => (string) $tab['key'],
        'label' => $tab['label'] ?? 'Onglet',
        'description' => $tab['description'] ?? '',
        'items' => collect($tab['items'])->map(function ($item) {
            $label = $item['label'] ?? data_get($item, 'name') ?? 'Entrée';
            return [
                'id' => (string) ($item['id'] ?? $item['value'] ?? ''),
                'label' => $label,
                'meta' => $item['meta'] ?? data_get($item, 'meta') ?? '',
                'details' => $item['details'] ?? '',
            ];
        })->values()->all(),
        'multi' => $tab['multi'] ?? false,
        'field' => $tab['field'] ?? null,
        'placeholder' => $tab['placeholder'] ?? 'Rechercher...',
        'initialSelected' => collect($tab['initialSelected'] ?? [])->filter(fn ($value) => $value !== null && $value !== '')->map(fn ($value) => (string) $value)->values()->all(),
    ];
})->values()->all();
@endphp

<script>
    if (!window.messageComposer) {
        window.messageComposer = function (initialTabs = []) {
            const sanitizedTabs = Array.isArray(initialTabs)
                ? initialTabs.filter((tab) => tab && tab.key)
                : [];
            const selected = {};
            sanitizedTabs.forEach((tab) => {
                selected[tab.key] = Array.isArray(tab.initialSelected) ? [...tab.initialSelected] : [];
            });

            return {
                tabs: sanitizedTabs,
                activeTab: sanitizedTabs[0]?.key ?? null,
                searchTerm: '',
                selected,
                get currentTab() {
                    return this.tabs.find((tab) => tab.key === this.activeTab) ?? null;
                },
                get activeItems() {
                    const tab = this.currentTab;
                    if (!tab) return [];
                    const term = this.searchTerm.trim().toLowerCase();
                    return tab.items
                        .map((item) => ({
                            ...item,
                            initials: this.makeInitials(item.label),
                        }))
                        .filter((item) => {
                            if (!term) return true;
                            const meta = item.meta ? item.meta.toLowerCase() : '';
                            return item.label.toLowerCase().includes(term) || meta.includes(term);
                        });
                },
                get selectedCount() {
                    return Object.values(this.selected).reduce((total, arr) => total + (Array.isArray(arr) ? arr.length : 0), 0);
                },
                get hasSelection() {
                    return this.selectedCount > 0;
                },
                get selectedChips() {
                    const chips = [];
                    this.tabs.forEach((tab) => {
                        (this.selected[tab.key] || []).forEach((value) => {
                            const item = tab.items.find((it) => String(it.id) === String(value));
                            if (item) {
                                chips.push({
                                    tab: tab.key,
                                    tabLabel: tab.label,
                                    id: value,
                                    label: item.label,
                                });
                            }
                        });
                    });
                    return chips;
                },
                makeInitials(label) {
                    if (!label) return '';
                    const words = label.split(/\s+/).filter(Boolean);
                    if (!words.length) return '';
                    if (words.length === 1) {
                        return words[0].slice(0, 2).toUpperCase();
                    }
                    return (words[0][0] + words[1][0]).toUpperCase();
                },
                setTab(key) {
                    if (this.activeTab === key) return;
                    this.activeTab = key;
                    this.searchTerm = '';
                },
                toggleSelection(tabKey, itemId) {
                    const tab = this.tabs.find((t) => t.key === tabKey);
                    if (!tab) return;
                    const normalizedId = String(itemId);
                    const current = this.selected[tabKey] || [];
                    if (tab.multi) {
                        if (current.includes(normalizedId)) {
                            this.selected[tabKey] = current.filter((value) => value !== normalizedId);
                        } else {
                            this.selected[tabKey] = [...current, normalizedId];
                        }
                    } else {
                        this.selected[tabKey] = current[0] === normalizedId ? [] : [normalizedId];
                    }
                },
                deselect(tabKey, itemId) {
                    this.toggleSelection(tabKey, itemId);
                },
                selectAll(tabKey) {
                    const tab = this.tabs.find((t) => t.key === tabKey);
                    if (!tab) return;
                    if (!tab.multi) {
                        this.selected[tabKey] = tab.items.length ? [String(tab.items[0].id)] : [];
                        return;
                    }
                    this.selected[tabKey] = tab.items.map((item) => String(item.id));
                },
                clearSelection(tabKey) {
                    if (!(tabKey in this.selected)) return;
                    this.selected[tabKey] = [];
                },
            };
        };
    }
</script>

<div x-data="messageComposer({{ Js::from($composerTabs) }})" class="space-y-6">
    <x-ui.page-header :title="$title" :subtitle="$subtitle">
        <x-slot name="actions">
            @if(!empty($backUrl))
                <x-ui.button :href="$backUrl" variant="secondary">
                    {{ $backLabel }}
                </x-ui.button>
            @endif
        </x-slot>
    </x-ui.page-header>

    <form action="{{ $action }}" method="POST" class="grid gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(320px,0.85fr)]">
        @csrf
        @if(strtoupper($method ?? 'POST') !== 'POST')
            @method($method)
        @endif

        <x-ui.card title="Contenu du message" subtitle="Renseignez un sujet clair et un texte facile à comprendre.">
            @if($errors->any())
                <x-ui.alert variant="error" class="mb-4">
                    <div class="font-semibold">Erreurs détectées</div>
                    <ul class="mt-2 list-disc pl-4 text-xs">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-ui.alert>
            @endif

            <div class="space-y-4">
                <x-ui.input
                    label="Sujet"
                    name="subject"
                    :value="old('subject', $subjectValue ?? '')"
                    placeholder="Résumé bref du message"
                />
                @error('subject')
                    <p class="app-error">{{ $message }}</p>
                @enderror

                <x-ui.textarea
                    label="Message"
                    name="body"
                    rows="10"
                    placeholder="Détaillez votre message..."
                >{{ old('body', $bodyValue ?? '') }}</x-ui.textarea>
                @error('body')
                    <p class="app-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-5 flex justify-end">
                <x-ui.button type="submit" variant="primary" x-bind:disabled="!hasSelection">
                    {{ $submitLabel }}
                </x-ui.button>
            </div>
        </x-ui.card>

        <x-ui.card title="Destinataires" subtitle="Sélectionnez une classe ou plusieurs personnes.">
            <div class="space-y-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-900" x-text="selectedCount + ' sélectionné(s)'"></p>
                        <p class="mt-1 text-xs text-slate-500" x-text="currentTab?.description || ''"></p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button type="button" class="app-button-ghost min-h-9 px-3 py-2 text-xs" @click="selectAll(activeTab)">
                            Tout sélectionner
                        </button>
                        <button type="button" class="app-button-ghost min-h-9 px-3 py-2 text-xs" @click="clearSelection(activeTab)">
                            Effacer
                        </button>
                    </div>
                </div>

                <div class="min-h-[52px] rounded-xl border border-slate-200 bg-slate-50 px-3 py-3">
                    <template x-for="chip in selectedChips" :key="chip.tab + chip.id">
                        <span class="mr-2 mt-2 inline-flex items-center gap-2 rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700">
                            <span x-text="chip.label"></span>
                            <button type="button" class="rounded-full p-1 text-sky-500 hover:text-sky-700" @click="deselect(chip.tab, chip.id)">
                                &times;
                            </button>
                        </span>
                    </template>
                    <p class="text-xs text-slate-400" x-show="selectedCount === 0">Aucun destinataire sélectionné.</p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white px-3 py-2">
                    <div class="relative flex items-center">
                        <svg class="absolute left-3 h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none">
                            <circle cx="11" cy="11" r="6" stroke="currentColor" stroke-width="2"></circle>
                            <path d="M16 16l4.5 4.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
                        </svg>
                        <input
                            x-model="searchTerm"
                            type="search"
                            placeholder="Rechercher un destinataire"
                            class="w-full rounded-2xl bg-transparent pl-10 text-sm text-slate-700 outline-none transition focus:ring-2 focus:ring-sky-200"
                        />
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <template x-for="tab in tabs" :key="tab.key">
                        <button
                            type="button"
                            class="rounded-full border px-4 py-2 text-xs font-semibold transition"
                            :class="{
                                'border-sky-200 bg-sky-50 text-sky-700': activeTab === tab.key,
                                'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50': activeTab !== tab.key
                            }"
                            @click="setTab(tab.key)"
                        >
                            <span x-text="tab.label"></span>
                        </button>
                    </template>
                </div>

                <div class="max-h-[340px] space-y-2 overflow-y-auto rounded-2xl border border-slate-200 bg-white p-3">
                    <template x-if="activeItems.length === 0">
                        <p class="py-6 text-center text-xs text-slate-400">Aucun destinataire disponible.</p>
                    </template>

                    <template x-for="item in activeItems" :key="item.id">
                        <button
                            type="button"
                            class="flex w-full items-center gap-3 rounded-2xl border px-3 py-3 text-left transition"
                            :class="selected[activeTab]?.includes(String(item.id)) ? 'border-sky-200 bg-sky-50' : 'border-slate-200 bg-white hover:bg-slate-50'"
                            @click="toggleSelection(activeTab, item.id)"
                        >
                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-100 text-sm font-semibold text-slate-700">
                                <span x-text="item.initials"></span>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-slate-900" x-text="item.label"></p>
                                <p class="text-xs text-slate-500" x-text="item.meta"></p>
                            </div>
                            <svg class="h-4 w-4 text-sky-600" viewBox="0 0 24 24" fill="none" :class="selected[activeTab]?.includes(String(item.id)) ? 'opacity-100' : 'opacity-0'">
                                <path d="M5 13l4 4 10-10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                            </svg>
                        </button>
                    </template>
                </div>

                <template x-for="tab in tabs" :key="tab.key + '-hidden'">
                    <template x-if="tab.field">
                        <template x-for="value in selected[tab.key]" :key="tab.key + '-' + value">
                            <input type="hidden" :name="tab.field" :value="value" />
                        </template>
                    </template>
                </template>
            </div>
        </x-ui.card>
    </form>
</div>
