@php
    $isStudents = $scope === 'students';
@endphp

<x-ui.card title="Cartes scolaires" subtitle="Generez, consultez et imprimez des cartes propres a l ecole active, avec QR code non sensible.">
    <form method="GET" class="grid gap-3 lg:grid-cols-[180px_minmax(0,1fr)_auto]">
        <select name="scope" class="app-input">
            <option value="students" @selected($isStudents)>Cartes eleves</option>
            <option value="parents" @selected(!$isStudents)>Cartes parents</option>
        </select>

        <input
            type="search"
            name="q"
            value="{{ $q }}"
            placeholder="{{ $isStudents ? 'Rechercher un eleve, une classe ou un parent' : 'Rechercher un parent, email ou telephone' }}"
            class="app-input"
        >

        <x-ui.button type="submit" variant="primary">Filtrer</x-ui.button>
    </form>
</x-ui.card>

<x-ui.card
    :title="$isStudents ? 'Cartes eleves' : 'Cartes parents'"
    :subtitle="$isStudents ? 'Chaque carte contient un token interne et un QR exploitable pour le pointage.' : 'Les cartes parents restent consultables et imprimables sans exposer de donnees sensibles.'"
>
    <div class="overflow-x-auto rounded-2xl border border-slate-200">
        <table class="app-table min-w-[880px]">
            <thead>
                <tr>
                    <th>{{ $isStudents ? 'Eleve' : 'Parent' }}</th>
                    <th>{{ $isStudents ? 'Classe' : 'Coordonnees' }}</th>
                    <th>{{ $isStudents ? 'Parent' : 'Enfants' }}</th>
                    <th>Code carte</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                    <tr>
                        <td>
                            <div class="font-semibold text-slate-950">{{ $isStudents ? $item->full_name : $item->name }}</div>
                            @if(!$isStudents && filled($item->email))
                                <div class="mt-1 text-xs text-slate-500">{{ $item->email }}</div>
                            @endif
                        </td>
                        <td>
                            @if($isStudents)
                                <div>{{ $item->classroom?->name ?? '-' }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $item->classroom?->level?->name ?? 'Niveau non renseigne' }}</div>
                            @else
                                <div>{{ $item->phone ?: '-' }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $item->email ?: 'Email non renseigne' }}</div>
                            @endif
                        </td>
                        <td class="max-w-[280px]">
                            @if($isStudents)
                                <div>{{ $item->parentUser?->name ?? '-' }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $item->parentUser?->phone ?? 'Telephone non renseigne' }}</div>
                            @else
                                <div class="break-words">
                                    {{ $item->children->pluck('full_name')->implode(', ') ?: '-' }}
                                </div>
                            @endif
                        </td>
                        <td>
                            <code class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ $item->card_token }}</code>
                        </td>
                        <td>
                            <div class="flex justify-end gap-2">
                                <x-ui.button
                                    :href="$isStudents ? route($showStudentRoute, $item) : route($showParentRoute, $item)"
                                    variant="secondary"
                                    class="px-3 py-2"
                                >
                                    Voir
                                </x-ui.button>

                                @if($canRegenerate)
                                    <form method="POST" action="{{ $isStudents ? route('admin.cards.students.regenerate', $item) : route('admin.cards.parents.regenerate', $item) }}" onsubmit="return confirm('Regenerer cette carte et remplacer son ancien QR code ?')">
                                        @csrf
                                        <x-ui.button type="submit" variant="outline" class="px-3 py-2">
                                            Regenerer
                                        </x-ui.button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-10 text-center text-sm text-slate-500">
                            Aucune carte ne correspond a cette recherche.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">
        {{ $items->links() }}
    </div>
</x-ui.card>
