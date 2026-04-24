<x-admin-layout title="Classe">
    <section class="app-card px-6 py-6 md:px-7">
        <div class="flex flex-col gap-6 xl:flex-row xl:items-start xl:justify-between">
            <div class="min-w-0">
                <p class="app-overline">Détail de la classe</p>
                <h2 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">
                    {{ $classroom->name }}
                    @if($classroom->section)
                        <span class="ml-2 text-lg font-medium text-slate-400">•</span>
                        <span class="text-lg font-semibold text-slate-600">{{ $classroom->section }}</span>
                    @endif
                </h2>

                <div class="mt-4 flex flex-wrap gap-2">
                    <x-ui.badge variant="info">Niveau : {{ $classroom->level?->name ?? '-' }}</x-ui.badge>
                    <x-ui.badge variant="info">{{ $students->count() }} élève(s)</x-ui.badge>
                    <x-ui.badge variant="warning">Ordre : nom (A à Z)</x-ui.badge>
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                <x-ui.button :href="route('admin.students.create', ['classroom_id' => $classroom->id])" variant="primary">
                    Ajouter un élève
                </x-ui.button>
                <x-ui.button :href="route('admin.structure.index')" variant="secondary">
                    Retour à la structure
                </x-ui.button>
            </div>
        </div>
    </section>

    <section class="app-card overflow-hidden p-0">
        <div class="flex flex-col gap-3 border-b border-slate-200 px-6 py-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="app-overline">Effectif</p>
                <h2 class="mt-2 text-lg font-semibold text-slate-900">Liste des élèves</h2>
                <p class="mt-1 text-sm text-slate-500">Consultez rapidement les informations principales et accédez aux actions de gestion.</p>
            </div>
            <div class="text-sm text-slate-500">Total : <span class="font-semibold text-slate-900">{{ $students->count() }}</span></div>
        </div>

        <div class="overflow-x-auto">
            <table class="app-table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Nom complet</th>
                    <th>Parent</th>
                    <th class="text-right">Actions</th>
                </tr>
                </thead>

                <tbody>
                @forelse($students as $s)
                    <tr>
                        <td>{{ $loop->iteration }}</td>

                        <td>
                            <div class="font-semibold text-slate-900">{{ $s->full_name }}</div>
                            @if($s->birth_date)
                                <div class="mt-1 text-xs text-slate-500">
                                    Né(e) le {{ \Carbon\Carbon::parse($s->birth_date)->format('d/m/Y') }}
                                </div>
                            @endif
                        </td>

                        <td>
                            <div class="font-medium text-slate-800">{{ $s->parentUser?->name ?? '-' }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ $s->parentUser?->phone ?? '—' }}</div>
                        </td>

                        <td>
                            <div class="flex flex-wrap justify-end gap-2">
                                <x-ui.button :href="route('admin.students.edit', $s)" variant="secondary" size="sm">Modifier</x-ui.button>

                                <form method="POST" action="{{ route('admin.students.archive', $s) }}"
                                      onsubmit="return confirm('Archiver cet eleve ? Son historique sera conserve.')">
                                    @csrf
                                    <x-ui.button type="submit" variant="ghost" size="sm">Archiver</x-ui.button>
                                </form>

                                <form method="POST" action="{{ route('admin.students.destroy', $s) }}"
                                      onsubmit="return confirm('Supprimer cet élève ?')">
                                    @csrf
                                    @method('DELETE')
                                    <x-ui.button type="submit" variant="danger" size="sm">Supprimer</x-ui.button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-14 text-center text-sm text-slate-500">
                            Aucun élève dans cette classe.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-admin-layout>
