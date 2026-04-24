<x-school-life-layout title="Activites" subtitle="Participation des eleves, presence et suivi terrain des activites.">
    <x-ui.page-header title="Activites scolaires" subtitle="Vue operationnelle pour confirmer, pointer et suivre les activites du jour.">
        <x-slot name="actions">
            <x-ui.button :href="route('admin.activities.index')" variant="secondary">Gestion admin</x-ui.button>
            <x-ui.button :href="route('school-life.events.index')" variant="primary">Agenda hebdo</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.card title="Filtres" subtitle="Filtrez la liste selon la classe, l enseignant et l etat de participation.">
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
            <select name="status" class="app-input">
                <option value="">Tous les etats</option>
                <option value="pending" @selected($status === 'pending')>Participation en attente</option>
                <option value="confirmed" @selected($status === 'confirmed')>Participation confirmee</option>
            </select>
            <x-ui.button type="submit" variant="primary">Filtrer</x-ui.button>
            <x-ui.button :href="route('school-life.activities.index')" variant="secondary">Reinitialiser</x-ui.button>
        </form>
    </x-ui.card>

    <x-ui.card title="Activites" subtitle="Ouvrez une activite pour gerer les participants, presences et rapports.">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse($activities as $activity)
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-center justify-between gap-2">
                        <p class="font-semibold text-slate-900">{{ $activity->title }}</p>
                        <span class="inline-flex h-3 w-3 rounded-full" style="background-color: {{ $activity->color ?: \App\Models\Activity::defaultColorForType((string) $activity->type) }}"></span>
                    </div>
                    <p class="mt-2 text-xs text-slate-500">{{ \App\Models\Activity::labelForType((string) $activity->type) }}</p>
                    <p class="mt-3 text-sm text-slate-600">{{ $activity->start_date?->format('d/m/Y H:i') }} -> {{ $activity->end_date?->format('d/m/Y H:i') }}</p>
                    <p class="mt-1 text-sm text-slate-600">Classe: {{ $activity->classroom?->name ?? 'Toutes' }}</p>
                    <p class="mt-1 text-sm text-slate-600">Enseignant: {{ $activity->teacher?->name ?? '-' }}</p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <x-ui.badge variant="info">Inscrits: {{ (int) $activity->participants_count }}</x-ui.badge>
                        <x-ui.badge variant="success">Confirmes: {{ (int) $activity->confirmed_count }}</x-ui.badge>
                        <x-ui.badge variant="warning">Presents: {{ (int) $activity->attended_count }}</x-ui.badge>
                    </div>
                    <div class="mt-4">
                        <x-ui.button :href="route('school-life.activities.show', $activity)" variant="primary" size="sm">Gerer activite</x-ui.button>
                    </div>
                </article>
            @empty
                <div class="student-empty md:col-span-2 xl:col-span-3">Aucune activite disponible.</div>
            @endforelse
        </div>
        <div class="mt-4">{{ $activities->links() }}</div>
    </x-ui.card>
</x-school-life-layout>
