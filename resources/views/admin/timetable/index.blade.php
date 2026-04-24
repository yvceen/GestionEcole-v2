<x-admin-layout title="Emploi du temps">
    <x-ui.page-header
        title="Planning hebdomadaire"
        subtitle="Consultez la grille par classe, ajustez les creneaux et gardez une lecture nette des horaires."
    >
        <x-slot name="actions">
            <x-ui.button :href="route('admin.timetable.create', ['classroom_id' => $selectedClassroomId])" variant="primary">
                Ajouter un creneau
            </x-ui.button>
            <x-ui.button :href="route('admin.timetable.settings.edit')" variant="secondary">
                Parametres
            </x-ui.button>
            <button type="button" onclick="window.print()" class="app-button-secondary">Imprimer</button>
        </x-slot>
    </x-ui.page-header>

    <section class="app-card mb-6 p-5">
        <form method="GET" action="{{ route('admin.timetable.index') }}" class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
            <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_240px]">
                <div>
                    <label for="classroom_id" class="mb-2 block text-sm font-semibold text-slate-700">Classe</label>
                    <select id="classroom_id" name="classroom_id" class="app-input">
                        @foreach($classrooms as $classroom)
                            <option value="{{ $classroom->id }}" @selected((int) $selectedClassroomId === (int) $classroom->id)>
                                {{ $classroom->name }} @if($classroom->level) ({{ $classroom->level->name }}) @endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                    <p class="font-semibold text-slate-900">{{ $selectedClass?->name ?? 'Classe' }}</p>
                    <p class="mt-1 text-xs">Selectionnez une autre classe pour recharger la grille.</p>
                </div>
            </div>
            <button type="submit" class="app-button-secondary">Filtrer</button>
        </form>
    </section>

    @if(session('success'))
        <section class="app-card mb-6 border-teal-200 bg-teal-50 p-4 text-sm text-teal-800">
            {{ session('success') }}
        </section>
    @endif

    @if($errors->any())
        <section class="app-card mb-6 border-red-200 bg-red-50 p-4 text-sm text-red-700">
            <ul class="space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </section>
    @endif

    @if($selectedClassroomId <= 0)
        <section class="app-card p-6 text-sm text-slate-600">
            Aucune classe active disponible.
        </section>
    @else
        @include('partials.timetable-grid', [
            'settings' => $settings,
            'slots' => $slots,
            'selectedClass' => $selectedClass,
            'days' => $days,
            'times' => $times,
            'slotsByDay' => $slotsByDay,
            'lunchBlock' => $lunchBlock,
            'totalMinutes' => $totalMinutes,
            'editable' => true,
            'editRouteName' => 'admin.timetable.edit',
            'deleteRouteName' => 'admin.timetable.destroy',
            'moveRouteName' => 'admin.timetable.move',
        ])
        {{-- ancien composant laissé pour compatibilité si besoin --}}
        {{-- <x-timetable.week-grid
            :days="$days"
            :slots-by-day="$slotsByDay"
            :editable="true"
            edit-route-name="admin.timetable.edit"
            delete-route-name="admin.timetable.destroy"
        /> --}}
    @endif
</x-admin-layout>
