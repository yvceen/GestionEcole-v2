<x-parent-layout title="Mes cartes" subtitle="Retrouvez votre carte parent et les cartes eleves de vos enfants dans un espace simple, imprimable et lisible sur mobile.">
    <section class="grid gap-4 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
        <x-ui.card title="Carte parent" subtitle="Acces rapide a votre carte numerique.">
            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                <p class="text-sm font-semibold text-slate-950">{{ $parent->name }}</p>
                <p class="mt-1 text-sm text-slate-600">{{ $parent->email ?: 'Email non renseigne' }}</p>
                <p class="mt-3 text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Code</p>
                <p class="mt-2 font-semibold text-slate-900">{{ $parent->card_token }}</p>
                <div class="mt-4">
                    <x-ui.button :href="route('parent.cards.self')" variant="primary">
                        Voir ma carte
                    </x-ui.button>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card title="Cartes enfants" subtitle="Chaque enfant garde sa propre carte eleve avec QR de pointage.">
            <div class="space-y-3">
                @forelse($children as $child)
                    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-semibold text-slate-950">{{ $child->full_name }}</p>
                                <p class="mt-1 text-sm text-slate-600">{{ $child->classroom?->name ?? '-' }}</p>
                                <p class="mt-2 text-xs text-slate-500">{{ $child->card_token }}</p>
                            </div>
                            <x-ui.button :href="route('parent.cards.children.show', $child)" variant="secondary">
                                Ouvrir la carte
                            </x-ui.button>
                        </div>
                    </div>
                @empty
                    <div class="student-empty">Aucune carte enfant disponible.</div>
                @endforelse
            </div>
        </x-ui.card>
    </section>
</x-parent-layout>
