<x-dynamic-component :component="$layoutComponent" title="Classe">
    <section class="app-card px-6 py-6 md:px-7">
        <div class="flex flex-col gap-6 xl:flex-row xl:items-start xl:justify-between">
            <div>
                <p class="app-overline">Détail de la classe</p>
                <h2 class="mt-2 text-3xl font-semibold text-slate-950">{{ $classroom->name }}</h2>
                <div class="mt-4 flex flex-wrap gap-2">
                    <x-ui.badge variant="info">Cycle : {{ $classroom->level?->cycle?->name ?? '-' }}</x-ui.badge>
                    <x-ui.badge variant="info">Niveau : {{ $classroom->level?->name ?? '-' }}</x-ui.badge>
                    <x-ui.badge variant="info">Section : {{ $classroom->section ?? '-' }}</x-ui.badge>
                    <x-ui.badge variant="info">{{ $students->count() }} élève(s)</x-ui.badge>
                    @if($classroom->capacity)<x-ui.badge variant="warning">Capacité : {{ $classroom->capacity }}</x-ui.badge>@endif
                </div>
            </div>
            <div class="flex flex-wrap gap-3">
                @if($routePrefix === 'admin.structure')
                    <x-ui.button :href="route('admin.students.create', ['classroom_id' => $classroom->id])" variant="primary">Ajouter un élève</x-ui.button>
                @endif
                <x-ui.button :href="route($routePrefix.'.index')" variant="secondary">Retour à la structure</x-ui.button>
            </div>
        </div>
    </section>

    <section class="app-card overflow-hidden p-0">
        <div class="border-b border-slate-200 px-6 py-5">
            <p class="app-overline">Effectif</p>
            <h2 class="mt-2 text-lg font-semibold text-slate-900">Liste des élèves</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="app-table">
                <thead><tr><th>#</th><th>Nom complet</th><th>Parent</th><th class="text-right">Actions</th></tr></thead>
                <tbody>
                @forelse($students as $student)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            <div class="font-semibold text-slate-900">{{ $student->full_name }}</div>
                            @if($student->birth_date)<div class="mt-1 text-xs text-slate-500">Né(e) le {{ \Carbon\Carbon::parse($student->birth_date)->format('d/m/Y') }}</div>@endif
                        </td>
                        <td>
                            <div class="font-medium text-slate-800">{{ $student->parentUser?->name ?? '-' }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ $student->parentUser?->phone ?? '-' }}</div>
                        </td>
                        <td>
                            <div class="flex flex-wrap justify-end gap-2">
                                @if($routePrefix === 'admin.structure')
                                    <x-ui.button :href="route('admin.students.edit', $student)" variant="secondary" size="sm">Modifier</x-ui.button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-6 py-14 text-center text-sm text-slate-500">Aucun élève dans cette classe.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-dynamic-component>
