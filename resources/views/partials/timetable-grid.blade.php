@props([
    'settings',
    'slots' => collect(),
    'selectedClass' => null,
    'days' => [],
    'times' => [],
    'slotsByDay' => collect(),
    'lunchBlock' => null,
    'totalMinutes' => 1,
    'editable' => false,
    'editRouteName' => null,
    'deleteRouteName' => null,
    'moveRouteName' => null,
])

@php
    $timelineRows = count($times) > 1 ? count($times) - 1 : 1;
@endphp

<section class="app-card overflow-hidden"
    @if($editable)
        x-data="adminTimetableGridDnD({
            dayStart: '{{ substr((string) $settings->day_start_time, 0, 5) }}',
            dayEnd: '{{ substr((string) $settings->day_end_time, 0, 5) }}',
            slotMinutes: {{ (int) $settings->slot_minutes }},
            totalMinutes: {{ $totalMinutes }},
            csrf: '{{ csrf_token() }}'
        })"
    @endif
>
    <header class="flex flex-col gap-3 border-b border-slate-200 bg-[linear-gradient(135deg,rgba(14,165,233,0.08),rgba(248,250,252,0.92))] px-5 py-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h3 class="text-base font-semibold text-slate-900">
                Semaine - {{ $selectedClass?->name ?? 'Classe' }}
            </h3>
            <p class="mt-1 text-xs text-slate-600">
                {{ substr((string) $settings->day_start_time, 0, 5) }} - {{ substr((string) $settings->day_end_time, 0, 5) }}
                | Seance standard : {{ (int) $settings->slot_minutes }} min
            </p>
        </div>
        @if($settings->lunch_start && $settings->lunch_end)
            <span class="app-badge app-badge-warning whitespace-nowrap">
                Pause: {{ substr((string) $settings->lunch_start, 0, 5) }} - {{ substr((string) $settings->lunch_end, 0, 5) }}
            </span>
        @endif
    </header>

    @if($slots->isEmpty())
        <div class="p-6 text-sm text-slate-600">Aucun creneau pour cette classe.</div>
    @else
        <div class="overflow-x-auto bg-slate-50/40">
            <div class="min-w-[1040px] p-3">
                <div class="grid grid-cols-[104px_repeat(6,minmax(144px,1fr))] overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-r border-slate-200 bg-slate-50 px-4 py-4 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">
                        Heure
                    </div>
                    @foreach($days as $dayLabel)
                        <div class="border-b border-r border-slate-200 bg-slate-50 px-4 py-4 text-center text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 last:border-r-0">
                            {{ $dayLabel }}
                        </div>
                    @endforeach

                    <div class="relative border-r border-slate-200 bg-slate-50/70">
                        <div class="relative h-[720px]" data-timetable-day-column="0">
                            @foreach($times as $index => $time)
                                @php
                                    $top = $timelineRows > 0 ? ($index / $timelineRows) * 100 : 0;
                                @endphp
                                <div class="absolute left-0 right-0" style="top: {{ $top }}%;">
                                    <div class="-translate-y-1/2 px-4 text-[11px] font-semibold text-slate-500">{{ $time }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    @foreach($days as $dayNumber => $dayLabel)
                        @php $daySlots = $slotsByDay->get($dayNumber, collect()); @endphp
                        <div class="relative border-r border-slate-200 bg-white last:border-r-0">
                            <div class="relative h-[720px]" data-timetable-day-column="{{ $dayNumber }}">
                                @for($i = 0; $i <= $timelineRows; $i++)
                                    @php $lineTop = $timelineRows > 0 ? ($i / $timelineRows) * 100 : 0; @endphp
                                    <div class="absolute left-0 right-0 border-t border-dashed border-slate-100" style="top: {{ $lineTop }}%;"></div>
                                @endfor

                                @if($lunchBlock)
                                    <div class="absolute left-2 right-2 rounded-2xl border border-amber-200 bg-amber-50/90 shadow-sm" style="{{ $lunchBlock['style'] }}">
                                        <div class="px-3 py-2 text-[10px] font-semibold uppercase tracking-[0.14em] text-amber-700">
                                            {{ $lunchBlock['label'] }}
                                        </div>
                                    </div>
                                @endif

                                @foreach($daySlots as $slot)
                                    <article
                                        class="absolute left-2 right-2 overflow-hidden rounded-2xl border border-slate-200 {{ $slot->grid_bg_class }} px-3 py-2.5 text-slate-800 shadow-sm transition-all duration-200 hover:-translate-y-[1px] hover:shadow-md"
                                        style="{{ $slot->grid_style }} border-left-width: 4px; border-left-color: {{ $slot->grid_border_color }};"
                                        data-slot-id="{{ $slot->id }}"
                                        data-day="{{ $dayNumber }}"
                                        data-start-minutes="{{ (int) $slot->grid_start_minutes }}"
                                        data-end-minutes="{{ (int) $slot->grid_end_minutes }}"
                                        @if($editable && $moveRouteName)
                                            data-move-url="{{ route($moveRouteName, $slot) }}"
                                            @mousedown.prevent="startDrag($event, $el)"
                                            :class="{ 'shadow-xl ring-2 ring-blue-300 cursor-grabbing z-30': activeSlotId == {{ $slot->id }}, 'cursor-grab': activeSlotId != {{ $slot->id }} }"
                                        @endif
                                    >
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-semibold leading-tight text-slate-900">{{ $slot->subject }}</p>
                                                <p class="mt-1 text-[11px] text-slate-600">{{ $slot->teacher?->name ?? 'Prof non assigne' }}</p>
                                            </div>
                                            <span class="shrink-0 rounded-full bg-white px-2 py-1 text-[10px] font-semibold text-slate-700 shadow-sm" data-role="time-label">
                                                {{ $slot->start_label }}-{{ $slot->end_label }}
                                            </span>
                                        </div>

                                        <div class="mt-2 flex items-center justify-between gap-2 text-[10px] text-slate-600">
                                            <span>Salle: {{ $slot->room ?: '-' }}</span>
                                            @if($editable && $moveRouteName)
                                                <span class="rounded-full bg-white/80 px-2 py-0.5 font-semibold text-slate-500">Glisser</span>
                                            @endif
                                        </div>

                                        @if($editable && $editRouteName && $deleteRouteName)
                                            <div class="mt-2 flex items-center gap-1.5">
                                                <a href="{{ route($editRouteName, $slot) }}" class="rounded-full border border-slate-200 bg-white px-2.5 py-1 text-[10px] font-semibold text-slate-700 transition hover:bg-slate-50">
                                                    Modifier
                                                </a>
                                                <form method="POST" action="{{ route($deleteRouteName, $slot) }}" onsubmit="return confirm('Supprimer ce creneau ?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="rounded-full border border-rose-200 bg-rose-50 px-2.5 py-1 text-[10px] font-semibold text-rose-700 transition hover:bg-rose-100">
                                                        Supprimer
                                                    </button>
                                                </form>
                                            </div>
                                        @endif

                                        @if($editable && $moveRouteName)
                                            <button
                                                type="button"
                                                class="absolute bottom-0 left-0 right-0 h-2 cursor-ns-resize bg-slate-300/45 transition hover:bg-slate-400/55"
                                                @mousedown.stop.prevent="startResize($event, $el)"
                                                aria-label="Redimensionner"
                                            ></button>
                                        @endif
                                    </article>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</section>

@if($editable)
    <script>
        function adminTimetableGridDnD(config) {
            return {
                activeSlotId: null,
                mode: null,
                activeEl: null,
                startY: 0,
                originStart: 0,
                originEnd: 0,
                pendingStart: 0,
                pendingEnd: 0,
                dayStartAbs: 0,
                minutePx: 1,
                init() {
                    this.dayStartAbs = this.toAbsoluteMinutes(config.dayStart);
                    this.minutePx = 720 / Math.max(1, config.totalMinutes);
                },
                startDrag(event, el) {
                    this.begin(event, el, 'drag');
                },
                startResize(event, el) {
                    this.begin(event, el, 'resize');
                },
                begin(event, el, mode) {
                    this.mode = mode;
                    this.activeEl = el;
                    this.activeSlotId = Number(el.dataset.slotId);
                    this.startY = event.clientY;
                    this.originStart = Number(el.dataset.startMinutes);
                    this.originEnd = Number(el.dataset.endMinutes);
                    this.pendingStart = this.originStart;
                    this.pendingEnd = this.originEnd;

                    this._moveHandler = (e) => this.onMove(e);
                    this._upHandler = () => this.onEnd();
                    window.addEventListener('mousemove', this._moveHandler);
                    window.addEventListener('mouseup', this._upHandler, { once: true });
                },
                onMove(event) {
                    if (!this.activeEl) return;

                    const deltaMinutes = (event.clientY - this.startY) / this.minutePx;
                    if (this.mode === 'drag') {
                        const duration = this.originEnd - this.originStart;
                        let nextStart = this.snap(this.originStart + deltaMinutes);
                        nextStart = Math.max(0, Math.min(nextStart, config.totalMinutes - duration));
                        this.pendingStart = nextStart;
                        this.pendingEnd = nextStart + duration;
                    } else {
                        let nextEnd = this.snap(this.originEnd + deltaMinutes);
                        const minEnd = this.originStart + config.slotMinutes;
                        nextEnd = Math.max(minEnd, Math.min(nextEnd, config.totalMinutes));
                        this.pendingStart = this.originStart;
                        this.pendingEnd = nextEnd;
                    }

                    this.paint(this.activeEl, this.pendingStart, this.pendingEnd);
                },
                async onEnd() {
                    if (!this.activeEl) return;
                    window.removeEventListener('mousemove', this._moveHandler);

                    const el = this.activeEl;
                    const oldStart = this.originStart;
                    const oldEnd = this.originEnd;
                    const newStart = this.pendingStart;
                    const newEnd = this.pendingEnd;
                    const day = Number(el.dataset.day);

                    const revert = () => {
                        this.paint(el, oldStart, oldEnd);
                        this.commitDataset(el, oldStart, oldEnd);
                    };

                    if (newStart === oldStart && newEnd === oldEnd) {
                        this.cleanup();
                        return;
                    }

                    if (this.hasOverlap(day, Number(el.dataset.slotId), newStart, newEnd)) {
                        alert('Ce creneau chevauche un autre creneau.');
                        revert();
                        this.cleanup();
                        return;
                    }

                    try {
                        const response = await fetch(el.dataset.moveUrl, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': config.csrf,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                start_time: this.minutesToTime(newStart),
                                end_time: this.minutesToTime(newEnd),
                            }),
                        });

                        if (!response.ok) {
                            const data = await response.json().catch(() => ({}));
                            throw new Error(data.message || 'Mise a jour impossible');
                        }

                        this.commitDataset(el, newStart, newEnd);
                        this.updateLabel(el, newStart, newEnd);
                    } catch (error) {
                        alert(error.message || 'Erreur lors de la mise a jour.');
                        revert();
                    }

                    this.cleanup();
                },
                cleanup() {
                    this.activeSlotId = null;
                    this.activeEl = null;
                    this.mode = null;
                },
                paint(el, startMinutes, endMinutes) {
                    const topPct = (startMinutes / config.totalMinutes) * 100;
                    const heightPct = ((endMinutes - startMinutes) / config.totalMinutes) * 100;
                    el.style.top = `${topPct}%`;
                    el.style.height = `${heightPct}%`;
                },
                commitDataset(el, startMinutes, endMinutes) {
                    el.dataset.startMinutes = String(startMinutes);
                    el.dataset.endMinutes = String(endMinutes);
                },
                updateLabel(el, startMinutes, endMinutes) {
                    const label = el.querySelector('[data-role="time-label"]');
                    if (label) {
                        label.textContent = `${this.minutesToTime(startMinutes)}-${this.minutesToTime(endMinutes)}`;
                    }
                },
                snap(value) {
                    return Math.round(value / config.slotMinutes) * config.slotMinutes;
                },
                hasOverlap(day, slotId, nextStart, nextEnd) {
                    const list = this.$root.querySelectorAll(`[data-slot-id][data-day="${day}"]`);
                    for (const other of list) {
                        const otherId = Number(other.dataset.slotId);
                        if (otherId === slotId) continue;
                        const otherStart = Number(other.dataset.startMinutes);
                        const otherEnd = Number(other.dataset.endMinutes);
                        if (nextStart < otherEnd && nextEnd > otherStart) {
                            return true;
                        }
                    }
                    return false;
                },
                toAbsoluteMinutes(hhmm) {
                    const [h, m] = hhmm.split(':').map(Number);
                    return (h * 60) + m;
                },
                minutesToTime(relativeMinutes) {
                    const absolute = this.dayStartAbs + Math.round(relativeMinutes);
                    const h = Math.floor(absolute / 60);
                    const m = absolute % 60;
                    return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
                },
            };
        }
    </script>
@endif
