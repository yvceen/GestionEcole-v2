@php
    $timetable = $timetable ?? null;
@endphp

<div class="grid gap-4 md:grid-cols-2">
    <x-ui.select label="Classe" name="classroom_id" id="classroom_id" required>
        <option value="">Sélectionner une classe</option>
        @foreach($classrooms as $classroom)
            <option
                value="{{ $classroom->id }}"
                @selected((int) old('classroom_id', $timetable->classroom_id ?? $selectedClassroomId ?? 0) === (int) $classroom->id)
            >
                {{ $classroom->name }} @if($classroom->level) ({{ $classroom->level->name }}) @endif
            </option>
        @endforeach
    </x-ui.select>

    <x-ui.select label="Jour" name="day" id="day" required>
        <option value="">Sélectionner un jour</option>
        @foreach($days as $dayNumber => $dayLabel)
            <option value="{{ $dayNumber }}" @selected((int) old('day', $timetable->day ?? 0) === (int) $dayNumber)>
                {{ $dayLabel }}
            </option>
        @endforeach
    </x-ui.select>

    <x-ui.input id="start_time" type="time" name="start_time" label="Heure de début" min="{{ substr((string) $settings->day_start_time, 0, 5) }}" max="{{ substr((string) $settings->day_end_time, 0, 5) }}" step="60" :value="old('start_time', isset($timetable->start_time) ? substr((string) $timetable->start_time, 0, 5) : '')" required />

    <x-ui.input id="end_time" type="time" name="end_time" label="Heure de fin" min="{{ substr((string) $settings->day_start_time, 0, 5) }}" max="{{ substr((string) $settings->day_end_time, 0, 5) }}" step="60" :value="old('end_time', isset($timetable->end_time) ? substr((string) $timetable->end_time, 0, 5) : '')" required />

    <x-ui.input id="subject" type="text" name="subject" label="Matière" :value="old('subject', $timetable->subject ?? '')" placeholder="Ex. : Mathématiques" required />

    <x-ui.select id="teacher_id" name="teacher_id" label="Enseignant (optionnel)">
        <option value="">Aucun</option>
        @foreach($teachers as $teacher)
            <option value="{{ $teacher->id }}" @selected((int) old('teacher_id', $timetable->teacher_id ?? 0) === (int) $teacher->id)>
                {{ $teacher->name }}
            </option>
        @endforeach
    </x-ui.select>

    <div class="md:col-span-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
        <p class="mb-3 text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Informations complementaires</p>
        <x-ui.input id="room" type="text" name="room" label="Salle (optionnel)" :value="old('room', $timetable->room ?? '')" placeholder="Ex. : Salle B12" />
    </div>
</div>
