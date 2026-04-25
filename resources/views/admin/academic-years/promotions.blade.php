<x-admin-layout title="Passage a l annee suivante" subtitle="Creez les placements de la prochaine annee sans dupliquer les comptes.">
    <x-ui.page-header title="Passage a l annee suivante" subtitle="Choisissez l annee source, l annee cible et la nouvelle affectation de chaque eleve.">
        <x-slot name="actions">
            <x-ui.button :href="route('admin.academic-years.index')" variant="secondary">Voir les annees</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.card title="Selection des annees" subtitle="L annee source conserve son historique. L annee cible recoit les nouveaux placements.">
        <form method="GET" class="grid gap-3 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto]">
            <select name="source_year_id" class="app-input">
                @foreach($years as $year)
                    <option value="{{ $year->id }}" @selected((int) $sourceYear->id === (int) $year->id)>Source: {{ $year->name }}</option>
                @endforeach
            </select>
            <select name="target_year_id" class="app-input">
                <option value="">Choisir l annee cible</option>
                @foreach($years as $year)
                    <option value="{{ $year->id }}" @selected($targetYear && (int) $targetYear->id === (int) $year->id)>Cible: {{ $year->name }}</option>
                @endforeach
            </select>
            <x-ui.button type="submit" variant="primary">Charger</x-ui.button>
        </form>
    </x-ui.card>

    @if($targetYear)
        <x-ui.card title="Placements" subtitle="Definissez le statut et la classe cible de chaque eleve.">
            <form method="POST" action="{{ route('admin.academic-promotions.store') }}" class="space-y-6">
                @csrf
                <input type="hidden" name="source_year_id" value="{{ $sourceYear->id }}">
                <input type="hidden" name="target_year_id" value="{{ $targetYear->id }}">

                @forelse($placements as $classroomId => $rows)
                    @php
                        $classroom = $rows->first()?->classroom;
                    @endphp
                    <div class="overflow-hidden rounded-2xl border border-slate-200">
                        <div class="border-b border-slate-200 bg-slate-50 px-5 py-4">
                            <h3 class="text-sm font-semibold text-slate-900">{{ $classroom?->name ?? 'Sans classe' }}</h3>
                            <p class="mt-1 text-xs text-slate-500">{{ $rows->count() }} eleve(s) a preparer pour {{ $targetYear->name }}</p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="app-table min-w-[980px]">
                                <thead>
                                    <tr>
                                        <th>Eleve</th>
                                        <th>Parent</th>
                                        <th>Statut</th>
                                        <th>Classe cible</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($rows as $placement)
                                        <tr>
                                            <td class="font-semibold text-slate-900">{{ $placement->student?->full_name }}</td>
                                            <td>{{ $placement->student?->parentUser?->name ?? '-' }}</td>
                                            <td>
                                                <select name="promotions[{{ $placement->student_id }}][status]" class="app-input">
                                                    @foreach($statuses as $status)
                                                        <option value="{{ $status }}" @selected($status === 'promoted')>{{ ucfirst($status) }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <select name="promotions[{{ $placement->student_id }}][classroom_id]" class="app-input">
                                                    <option value="">Aucune classe</option>
                                                    @foreach($classrooms as $classroomOption)
                                                        <option value="{{ $classroomOption->id }}" @selected((int) $classroomOption->id === (int) $placement->classroom_id)>
                                                            {{ $classroomOption->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center text-sm text-slate-500">
                        Aucun placement source disponible pour cette annee.
                    </div>
                @endforelse

                @if($placements->isNotEmpty())
                    <div class="flex flex-wrap gap-3">
                        <x-ui.button type="submit" variant="primary">Enregistrer les placements</x-ui.button>
                        <x-ui.button :href="route('admin.academic-promotions.index', ['source_year_id' => $sourceYear->id, 'target_year_id' => $targetYear->id])" variant="secondary">Recharger</x-ui.button>
                    </div>
                @endif
            </form>
        </x-ui.card>
    @endif
</x-admin-layout>
