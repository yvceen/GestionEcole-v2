<x-admin-layout title="Activites" subtitle="Suivi des activites scolaires, planification et participation des eleves.">
    <x-ui.page-header title="Activites scolaires" subtitle="Créez et planifiez sports, sorties, ateliers et clubs avec suivi de participation.">
        <x-slot name="actions">
            <x-ui.button :href="route('admin.activities.create')" variant="primary">Nouvelle activite</x-ui.button>
            <x-ui.button :href="route('admin.events.index')" variant="secondary">Ouvrir agenda</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.card title="Filtres" subtitle="Affinez la liste par classe, enseignant ou type.">
        <form method="GET" class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)_auto_auto]">
            <select name="classroom_id" class="app-input">
                <option value="">Toutes les classes</option>
                @foreach($classrooms as $classroom)
                    <option value="{{ $classroom->id }}" @selected((int) $classroomId === (int) $classroom->id)>{{ $classroom->name }}</option>
                @endforeach
            </select>
            <select name="teacher_id" class="app-input">
                <option value="">Tous les enseignants</option>
                @foreach($teachers as $teacher)
                    <option value="{{ $teacher->id }}" @selected((int) $teacherId === (int) $teacher->id)>{{ $teacher->name }}</option>
                @endforeach
            </select>
            <select name="type" class="app-input">
                <option value="">Tous les types</option>
                @foreach(\App\Models\Activity::types() as $activityType)
                    <option value="{{ $activityType }}" @selected($type === $activityType)>{{ \App\Models\Activity::labelForType($activityType) }}</option>
                @endforeach
            </select>
            <x-ui.button type="submit" variant="primary">Filtrer</x-ui.button>
            <x-ui.button :href="route('admin.activities.index')" variant="secondary">Reinitialiser</x-ui.button>
        </form>
    </x-ui.card>

    <x-ui.card title="Liste des activites" subtitle="Toutes les activites de l ecole active.">
        <div class="overflow-x-auto rounded-2xl border border-slate-200">
            <table class="app-table min-w-[920px]">
                <thead>
                    <tr>
                        <th>Titre</th>
                        <th>Type</th>
                        <th>Periode</th>
                        <th>Classe</th>
                        <th>Enseignant</th>
                        <th>Participants</th>
                        <th>Rapports</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($activities as $activity)
                        <tr>
                            <td class="font-semibold text-slate-900">{{ $activity->title }}</td>
                            <td><x-ui.badge variant="info">{{ \App\Models\Activity::labelForType((string) $activity->type) }}</x-ui.badge></td>
                            <td>
                                <div>{{ $activity->start_date?->format('d/m/Y H:i') }}</div>
                                <div class="text-xs text-slate-500">{{ $activity->end_date?->format('d/m/Y H:i') }}</div>
                            </td>
                            <td>{{ $activity->classroom?->name ?? '-' }}</td>
                            <td>{{ $activity->teacher?->name ?? '-' }}</td>
                            <td><x-ui.badge variant="success">{{ (int) $activity->participants_count }}</x-ui.badge></td>
                            <td><x-ui.badge variant="warning">{{ (int) $activity->reports_count }}</x-ui.badge></td>
                            <td>
                                <div class="flex flex-wrap gap-2">
                                    <x-ui.button :href="route('admin.activities.edit', $activity)" variant="secondary" size="sm">Modifier</x-ui.button>
                                    <form method="POST" action="{{ route('admin.activities.destroy', $activity) }}" onsubmit="return confirm('Supprimer cette activite ?')">
                                        @csrf
                                        @method('DELETE')
                                        <x-ui.button type="submit" variant="danger" size="sm">Supprimer</x-ui.button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-5 py-10 text-center text-sm text-slate-500">Aucune activite trouvee.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $activities->links() }}</div>
    </x-ui.card>
</x-admin-layout>
