@push('head')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css">
@endpush

@php
    $classroomFilter = $classroomId ?? 0;
    $teacherFilter = $teacherId ?? 0;
@endphp

<section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <article class="app-stat-card">
        <p class="app-stat-label">Evenements</p>
        <p class="app-stat-value">{{ $summary['total'] ?? 0 }}</p>
        <p class="app-stat-meta">Tous les blocs agenda de l ecole active.</p>
    </article>
    <article class="app-stat-card">
        <p class="app-stat-label">Cours</p>
        <p class="app-stat-value text-teal-700">{{ $summary['course'] ?? 0 }}</p>
        <p class="app-stat-meta">Seances affichees sur la semaine.</p>
    </article>
    <article class="app-stat-card">
        <p class="app-stat-label">Examens</p>
        <p class="app-stat-value text-rose-700">{{ $summary['exam'] ?? 0 }}</p>
        <p class="app-stat-meta">Evaluations et controles.</p>
    </article>
    <article class="app-stat-card">
        <p class="app-stat-label">Activites</p>
        <p class="app-stat-value text-sky-700">{{ $summary['activity'] ?? 0 }}</p>
        <p class="app-stat-meta">Sorties, clubs et temps forts.</p>
    </article>
</section>

<x-ui.card title="Filtres agenda" subtitle="Affinez la semaine affichee par classe ou enseignant, sans recharger la page.">
    <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto]">
        <div>
            <label class="app-label" for="agendaClassroom">Classe</label>
            <select id="agendaClassroom" class="app-input">
                <option value="">Toutes les classes</option>
                @foreach($classrooms as $classroom)
                    <option value="{{ $classroom->id }}" @selected((int) $classroomFilter === (int) $classroom->id)>
                        {{ $classroom->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="app-label" for="agendaTeacher">Enseignant</label>
            <select id="agendaTeacher" class="app-input">
                <option value="">Tous les enseignants</option>
                @foreach($teachers as $teacher)
                    <option value="{{ $teacher->id }}" @selected((int) $teacherFilter === (int) $teacher->id)>
                        {{ $teacher->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="flex flex-wrap items-end gap-3">
            @if($canManage)
                <x-ui.button :href="route('admin.events.create')" variant="primary">Ajouter un bloc</x-ui.button>
            @endif
            <x-ui.button type="button" variant="secondary" id="agendaResetFilters">Reinitialiser</x-ui.button>
        </div>
    </div>
</x-ui.card>

<x-ui.card title="Vue semaine" subtitle="Agenda hebdomadaire moderne avec blocs colores, optimise pour desktop et mobile.">
    <div class="rounded-[28px] border border-slate-200 bg-slate-50 p-3 sm:p-4">
        <div id="weeklyAgendaCalendar" class="min-h-[720px] rounded-[24px] bg-white p-2 sm:p-4"></div>
    </div>
</x-ui.card>

<x-ui.card title="Prochains blocs" subtitle="Vue de gestion rapide pour ouvrir, modifier ou supprimer les evenements a venir.">
    <div class="grid gap-4 lg:grid-cols-2">
        @forelse($upcomingEvents as $agendaEvent)
            <article class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-3">
                            <span class="h-3 w-3 rounded-full" style="background-color: {{ $agendaEvent->color ?: \App\Models\Event::defaultColorForType((string) $agendaEvent->type) }}"></span>
                            <h3 class="text-base font-semibold text-slate-900">{{ $agendaEvent->title }}</h3>
                        </div>
                        <p class="mt-2 text-sm text-slate-500">
                            {{ \App\Models\Event::labelForType((string) $agendaEvent->type) }}
                            @if($agendaEvent->classroom?->name)
                                - {{ $agendaEvent->classroom->name }}
                            @endif
                        </p>
                    </div>

                    @if($canManage)
                        <div class="flex gap-2">
                            <x-ui.button :href="route('admin.events.edit', $agendaEvent)" variant="secondary" size="sm">Modifier</x-ui.button>
                            <form method="POST" action="{{ route('admin.events.destroy', $agendaEvent) }}" onsubmit="return confirm('Supprimer cet evenement ?')">
                                @csrf
                                @method('DELETE')
                                <x-ui.button type="submit" variant="danger" size="sm">Supprimer</x-ui.button>
                            </form>
                        </div>
                    @endif
                </div>

                <div class="mt-4 flex flex-wrap gap-4 text-sm text-slate-600">
                    <span>{{ optional($agendaEvent->start)->format('d/m/Y H:i') ?? 'N/A' }}</span>
                    <span>{{ optional($agendaEvent->end)->format('d/m/Y H:i') ?? 'N/A' }}</span>
                    <span>{{ $agendaEvent->teacher?->name ?? 'Sans enseignant' }}</span>
                </div>
            </article>
        @empty
            <div class="rounded-[24px] border border-dashed border-slate-300 bg-white/70 px-6 py-10 text-center text-sm text-slate-500 lg:col-span-2">
                Aucun bloc agenda a venir pour ces filtres.
            </div>
        @endforelse
    </div>
</x-ui.card>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const calendarEl = document.getElementById('weeklyAgendaCalendar');
            if (!calendarEl || typeof FullCalendar === 'undefined') {
                return;
            }

            const classroomField = document.getElementById('agendaClassroom');
            const teacherField = document.getElementById('agendaTeacher');
            const resetButton = document.getElementById('agendaResetFilters');

            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: window.innerWidth < 768 ? 'timeGridDay' : 'timeGridWeek',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'timeGridWeek,timeGridDay',
                },
                allDaySlot: false,
                slotMinTime: '07:00:00',
                slotMaxTime: '20:00:00',
                height: 'auto',
                nowIndicator: true,
                editable: false,
                expandRows: true,
                locale: 'fr',
                firstDay: 1,
                dayHeaderFormat: { weekday: 'short', day: 'numeric', month: 'short' },
                eventTimeFormat: { hour: '2-digit', minute: '2-digit', meridiem: false },
                events(fetchInfo, successCallback, failureCallback) {
                    const params = new URLSearchParams({
                        start: fetchInfo.startStr,
                        end: fetchInfo.endStr,
                    });

                    if (classroomField?.value) {
                        params.set('classroom_id', classroomField.value);
                    }

                    if (teacherField?.value) {
                        params.set('teacher_id', teacherField.value);
                    }

                    fetch(@js(route('agenda.feed')) + '?' + params.toString(), {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        }
                    })
                        .then((response) => response.json())
                        .then((data) => successCallback(data))
                        .catch((error) => failureCallback(error));
                },
                eventDidMount(info) {
                    const props = info.event.extendedProps || {};
                    const tooltip = [
                        props.type || '',
                        props.classroom || '',
                        props.teacher || '',
                    ].filter(Boolean).join(' | ');

                    if (tooltip) {
                        info.el.setAttribute('title', tooltip);
                    }
                },
                eventContent(arg) {
                    const props = arg.event.extendedProps || {};
                    const wrap = document.createElement('div');
                    wrap.className = 'rounded-xl px-1 py-0.5 text-[11px] leading-4 sm:text-xs';
                    wrap.innerHTML = `
                        <div class="font-semibold">${arg.event.title}</div>
                        <div class="opacity-90">${props.classroom || ''}</div>
                        <div class="opacity-80">${props.teacher || ''}</div>
                    `;

                    return { domNodes: [wrap] };
                },
                windowResize() {
                    calendar.changeView(window.innerWidth < 768 ? 'timeGridDay' : 'timeGridWeek');
                },
            });

            calendar.render();

            [classroomField, teacherField].forEach((field) => {
                field?.addEventListener('change', () => calendar.refetchEvents());
            });

            resetButton?.addEventListener('click', () => {
                if (classroomField) classroomField.value = '';
                if (teacherField) teacherField.value = '';
                calendar.refetchEvents();
            });
        });
    </script>
@endpush
