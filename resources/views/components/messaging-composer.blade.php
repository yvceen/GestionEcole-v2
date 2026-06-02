@props([
    'title' => 'Nouveau message',
    'subtitle' => 'Envoyez un message clair et bien cible.',
    'action',
    'method' => 'POST',
    'backUrl' => null,
    'backLabel' => 'Retour',
    'submitLabel' => 'Envoyer',
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
            $label = $item['label'] ?? data_get($item, 'name') ?? 'Entree';
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
                            color: this.colorForTab(tab.key),
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
                                    color: this.colorForTab(tab.key),
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
                colorForTab(key) {
                    if (key === 'classes') return 'sky';
                    if (key === 'parents') return 'emerald';
                    if (key === 'staff') return 'violet';
                    return 'slate';
                },
                tabClasses(key) {
                    const active = this.activeTab === key;
                    if (key === 'classes') return active ? 'border-sky-200 bg-sky-600 text-white shadow-sm shadow-sky-100' : 'border-sky-100 bg-sky-50 text-sky-700 hover:bg-sky-100';
                    if (key === 'parents') return active ? 'border-emerald-200 bg-emerald-600 text-white shadow-sm shadow-emerald-100' : 'border-emerald-100 bg-emerald-50 text-emerald-700 hover:bg-emerald-100';
                    if (key === 'staff') return active ? 'border-violet-200 bg-violet-600 text-white shadow-sm shadow-violet-100' : 'border-violet-100 bg-violet-50 text-violet-700 hover:bg-violet-100';
                    return active ? 'border-slate-300 bg-slate-900 text-white' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50';
                },
                avatarClasses(color, active) {
                    if (color === 'sky') return active ? 'bg-sky-600 text-white' : 'bg-sky-50 text-sky-700';
                    if (color === 'emerald') return active ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-700';
                    if (color === 'violet') return active ? 'bg-violet-600 text-white' : 'bg-violet-50 text-violet-700';
                    return active ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700';
                },
                chipClasses(color) {
                    if (color === 'sky') return 'border-sky-200 bg-sky-50 text-sky-700';
                    if (color === 'emerald') return 'border-emerald-200 bg-emerald-50 text-emerald-700';
                    if (color === 'violet') return 'border-violet-200 bg-violet-50 text-violet-700';
                    return 'border-slate-200 bg-slate-50 text-slate-700';
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
    <section class="overflow-hidden rounded-[32px] border border-sky-100 bg-[radial-gradient(circle_at_top_left,_rgba(56,189,248,0.18),_transparent_30%),radial-gradient(circle_at_bottom_right,_rgba(16,185,129,0.14),_transparent_34%),linear-gradient(135deg,#ffffff,#f8fbff_58%,#eefdf8)] px-6 py-6 text-slate-950 shadow-xl shadow-slate-200/70 md:px-8">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="max-w-2xl">
                <div class="inline-flex items-center gap-2 rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-sky-700">
                    Composition
                </div>
                <h1 class="mt-4 text-3xl font-semibold tracking-tight md:text-4xl">Nouvelle discussion</h1>
                <p class="mt-3 text-sm leading-6 text-slate-600">{{ $subtitle }}</p>
            </div>

            @if(!empty($backUrl))
                <x-ui.button :href="$backUrl" variant="secondary">
                    {{ $backLabel }}
                </x-ui.button>
            @endif
        </div>
    </section>

    <form action="{{ $action }}" method="POST" class="grid gap-6 xl:grid-cols-[minmax(0,1.1fr)_minmax(340px,0.9fr)]">
        @csrf
        @if(strtoupper($method ?? 'POST') !== 'POST')
            @method($method)
        @endif
        <input type="hidden" name="subject" value="{{ old('subject', 'Discussion') }}">

        <section class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-6 py-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold tracking-tight text-slate-950">Message</h2>
                        <p class="mt-1 text-sm text-slate-500">Ecrivez votre message comme dans une conversation directe.</p>
                    </div>
                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-sky-50 text-sky-700 ring-1 ring-sky-100">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 5h16v10H7l-3 3V5Z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 9h8M8 12h5"/>
                        </svg>
                    </span>
                </div>
            </div>

            <div class="space-y-5 px-6 py-6">
                @if($errors->any())
                    <x-ui.alert variant="error">
                        <div class="font-semibold">Erreurs detectees</div>
                        <ul class="mt-2 list-disc pl-4 text-xs">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </x-ui.alert>
                @endif

                <x-ui.textarea
                    label="Message"
                    name="body"
                    rows="12"
                    placeholder="Ecrivez votre message..."
                >{{ old('body', $bodyValue ?? '') }}</x-ui.textarea>
                @error('body')
                    <p class="app-error">{{ $message }}</p>
                @enderror

                <div class="rounded-[24px] border border-slate-200 bg-slate-50 px-4 py-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-slate-950">Controle avant envoi</p>
                            <p class="mt-1 text-xs text-slate-500" x-text="hasSelection ? selectedCount + ' destinataire(s) selectionne(s)' : 'Selectionnez au moins un destinataire pour envoyer.'"></p>
                        </div>
                        <x-ui.button type="submit" variant="primary" x-bind:disabled="!hasSelection">
                            {{ $submitLabel }}
                        </x-ui.button>
                    </div>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-6 py-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold tracking-tight text-slate-950">Destinataires</h2>
                        <p class="mt-1 text-sm text-slate-500" x-text="currentTab?.description || 'Selectionnez les destinataires.'"></p>
                    </div>
                    <span class="inline-flex min-w-11 items-center justify-center rounded-2xl bg-emerald-50 px-3 py-2 text-sm font-bold text-emerald-700 ring-1 ring-emerald-100" x-text="selectedCount"></span>
                </div>
            </div>

            <div class="space-y-5 px-6 py-6">
                <div class="grid gap-2 sm:grid-cols-3">
                    <template x-for="tab in tabs" :key="tab.key">
                        <button
                            type="button"
                            class="rounded-2xl border px-4 py-3 text-left text-sm font-semibold transition"
                            :class="tabClasses(tab.key)"
                            @click="setTab(tab.key)"
                        >
                            <span class="block" x-text="tab.label"></span>
                            <span class="mt-1 block text-[11px] opacity-80" x-text="tab.items.length + ' disponible(s)'"></span>
                        </button>
                    </template>
                </div>

                <div class="min-h-[58px] rounded-[22px] border border-slate-200 bg-slate-50 px-3 py-3">
                    <template x-for="chip in selectedChips" :key="chip.tab + chip.id">
                        <span class="mr-2 mt-2 inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-semibold" :class="chipClasses(chip.color)">
                            <span x-text="chip.label"></span>
                            <button type="button" class="rounded-full px-1 opacity-70 hover:opacity-100" @click="deselect(chip.tab, chip.id)">
                                &times;
                            </button>
                        </span>
                    </template>
                    <p class="text-xs text-slate-400" x-show="selectedCount === 0">Aucun destinataire selectionne.</p>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white px-3 py-2 shadow-sm">
                    <div class="relative flex items-center">
                        <svg class="absolute left-3 h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none">
                            <circle cx="11" cy="11" r="6" stroke="currentColor" stroke-width="2"></circle>
                            <path d="M16 16l4.5 4.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
                        </svg>
                        <input
                            x-model="searchTerm"
                            type="search"
                            placeholder="Rechercher un destinataire"
                            class="w-full rounded-2xl bg-transparent py-2 pl-10 text-sm text-slate-700 outline-none transition focus:ring-2 focus:ring-sky-200"
                        />
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button type="button" class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50" @click="selectAll(activeTab)">
                        Tout selectionner
                    </button>
                    <button type="button" class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50" @click="clearSelection(activeTab)">
                        Effacer
                    </button>
                </div>

                <div class="max-h-[390px] space-y-2 overflow-y-auto rounded-[24px] border border-slate-200 bg-slate-50 p-3">
                    <template x-if="activeItems.length === 0">
                        <p class="py-8 text-center text-xs text-slate-400">Aucun destinataire disponible.</p>
                    </template>

                    <template x-for="item in activeItems" :key="item.id">
                        <button
                            type="button"
                            class="flex w-full items-center gap-3 rounded-2xl border px-3 py-3 text-left transition"
                            :class="selected[activeTab]?.includes(String(item.id)) ? 'border-slate-300 bg-white shadow-sm' : 'border-transparent bg-white/70 hover:bg-white'"
                            @click="toggleSelection(activeTab, item.id)"
                        >
                            <div class="flex h-11 w-11 items-center justify-center rounded-2xl text-sm font-bold" :class="avatarClasses(item.color, selected[activeTab]?.includes(String(item.id)))">
                                <span x-text="item.initials"></span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-slate-950" x-text="item.label"></p>
                                <p class="truncate text-xs text-slate-500" x-text="item.meta"></p>
                            </div>
                            <svg class="h-5 w-5 text-emerald-600 transition" viewBox="0 0 24 24" fill="none" :class="selected[activeTab]?.includes(String(item.id)) ? 'opacity-100' : 'opacity-0'">
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
        </section>
    </form>
</div>
