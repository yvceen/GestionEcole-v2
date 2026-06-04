@push('head')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css">
    <style>
        .myedu-agenda-shell .fc {
            --fc-border-color: #e2e8f0;
            --fc-page-bg-color: transparent;
            --fc-today-bg-color: rgba(14, 165, 233, 0.08);
            color: #0f172a;
        }
        .myedu-agenda-shell .fc .fc-toolbar {
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.65rem;
        }
        .myedu-agenda-shell .fc .fc-toolbar-title {
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: #0f172a;
        }
        .myedu-agenda-shell .fc .fc-button {
            border: 1px solid #dbeafe;
            border-radius: 999px;
            background: #ffffff;
            color: #075985;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.06);
            font-weight: 700;
            padding: 0.32rem 0.65rem;
        }
        .myedu-agenda-shell .fc .fc-button-primary:not(:disabled).fc-button-active,
        .myedu-agenda-shell .fc .fc-button-primary:not(:disabled):active {
            background: #0284c7;
            border-color: #0284c7;
            color: #ffffff;
        }
        .myedu-agenda-shell .fc .fc-col-header-cell {
            background: #f8fafc;
            padding: 0.35rem 0;
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .myedu-agenda-shell .fc .fc-timegrid-slot {
            height: 1.65rem;
        }
        .myedu-agenda-shell .fc .fc-timegrid-slot-label {
            font-size: 0.68rem;
        }
        .myedu-agenda-shell .fc .fc-event {
            border: 0;
            border-radius: 8px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
            overflow: hidden;
        }
        .myedu-agenda-shell .fc .fc-timegrid-event {
            padding: 0.05rem;
        }
        @media (max-width: 767px) {
            .myedu-agenda-shell .fc .fc-toolbar {
                align-items: stretch;
                flex-direction: column;
            }
            .myedu-agenda-shell .fc .fc-toolbar-chunk {
                display: flex;
                justify-content: center;
            }
        }
    </style>
@endpush

@php
    $classroomFilter = $classroomId ?? 0;
    $teacherFilter = $teacherId ?? 0;
@endphp

<section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <article class="app-stat-card border-sky-100 bg-sky-50/80">
        <p class="app-stat-label">Événements</p>
        <p class="app-stat-value">{{ $summary['total'] ?? 0 }}</p>
        <p class="app-stat-meta">Tous les blocs agenda de l'École active.</p>
    </article>
    <article class="app-stat-card border-teal-100 bg-teal-50/80">
        <p class="app-stat-label">Cours</p>
        <p class="app-stat-value text-teal-700">{{ $summary['course'] ?? 0 }}</p>
        <p class="app-stat-meta">Seances affichees sur la semaine.</p>
    </article>
    <article class="app-stat-card border-rose-100 bg-rose-50/80">
        <p class="app-stat-label">Examens</p>
        <p class="app-stat-value text-rose-700">{{ $summary['exam'] ?? 0 }}</p>
        <p class="app-stat-meta">Évaluations et controles.</p>
    </article>
    <article class="app-stat-card border-indigo-100 bg-indigo-50/80">
        <p class="app-stat-label">Activités</p>
        <p class="app-stat-value text-sky-700">{{ $summary['activity'] ?? 0 }}</p>
        <p class="app-stat-meta">Sorties, clubs et temps forts.</p>
    </article>
</section>

<x-ui.card title="Filtres agenda" subtitle="Affinez la semaine affichée par classe ou enseignant, sans recharger la page." class="mt-5">
    <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] lg:items-end">
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
                <x-ui.button :href="route('admin.events.create')" variant="primary" class="w-full sm:w-auto">Ajouter un bloc</x-ui.button>
            @endif
            <x-ui.button type="button" variant="secondary" id="agendaResetFilters" class="w-full sm:w-auto">Reinitialiser</x-ui.button>
        </div>
    </div>
    <div class="mt-5 flex flex-wrap gap-3 text-xs font-semibold text-slate-600">
        <span class="inline-flex items-center gap-2 rounded-full bg-teal-50 px-3 py-1.5 text-teal-700 ring-1 ring-teal-100"><span class="h-2 w-2 rounded-full bg-teal-500"></span>Cours</span>
        <span class="inline-flex items-center gap-2 rounded-full bg-rose-50 px-3 py-1.5 text-rose-700 ring-1 ring-rose-100"><span class="h-2 w-2 rounded-full bg-rose-500"></span>Examens</span>
        <span class="inline-flex items-center gap-2 rounded-full bg-sky-50 px-3 py-1.5 text-sky-700 ring-1 ring-sky-100"><span class="h-2 w-2 rounded-full bg-sky-500"></span>Activités</span>
    </div>
</x-ui.card>

<x-ui.card title="Vue semaine" subtitle="Agenda hebdomadaire moderne avec blocs colores, optimise pour desktop et mobile." class="mt-5">
    <div class="myedu-agenda-shell rounded-[22px] border border-slate-200 bg-gradient-to-br from-slate-50 to-sky-50/70 p-2 sm:p-3">
        <div id="weeklyAgendaCalendar" class="rounded-[18px] bg-white p-2 shadow-sm ring-1 ring-slate-200/80 sm:p-3"></div>
    </div>
</x-ui.card>

<x-ui.card title="Prochains blocs" subtitle="Vue de gestion rapide pour ouvrir, modifier ou supprimer les Événements a venir." class="mt-5">
    <div class="grid gap-4 xl:grid-cols-2">
        @forelse($upcomingEvents as $agendaEvent)
            <article class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-sky-200 hover:shadow-lg">
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
                        <div class="flex flex-wrap justify-end gap-2">
                            <x-ui.button :href="route('admin.events.edit', $agendaEvent)" variant="secondary" size="sm">Modifier</x-ui.button>
                            <form method="POST" action="{{ route('admin.events.destroy', $agendaEvent) }}" onsubmit="return confirm('Supprimer cet Événement ?')">
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
                    wrap.className = 'rounded-lg px-1 text-[10px] leading-3 sm:text-[11px]';
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
