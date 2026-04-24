<x-ui.card title="Bloc agenda" subtitle="Definissez un titre, un type, un horaire, une classe et une couleur pour l agenda hebdomadaire.">
    <form method="POST" action="{{ $action }}" class="space-y-6">
        @csrf
        @if(($method ?? 'POST') !== 'POST')
            @method($method)
        @endif

        <div class="grid gap-4 md:grid-cols-2">
            <x-ui.input
                name="title"
                label="Titre"
                :value="old('title', $event->title)"
                required
                class="md:col-span-2"
            />

            <div>
                <label class="app-label" for="type">Type</label>
                <select id="type" name="type" class="app-input" required>
                    @foreach($types as $eventType)
                        <option value="{{ $eventType }}" @selected(old('type', $event->type ?: \App\Models\Event::TYPE_COURSE) === $eventType)>
                            {{ \App\Models\Event::labelForType($eventType) }}
                        </option>
                    @endforeach
                </select>
                @error('type')<p class="app-error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="app-label" for="color">Couleur</label>
                <input
                    id="color"
                    type="color"
                    name="color"
                    value="{{ old('color', $event->color ?: \App\Models\Event::defaultColorForType((string) ($event->type ?: \App\Models\Event::TYPE_COURSE))) }}"
                    class="h-12 w-full rounded-2xl border border-slate-200 bg-white px-2 py-2"
                >
                @error('color')<p class="app-error">{{ $message }}</p>@enderror
            </div>

            <x-ui.input
                id="start"
                type="datetime-local"
                name="start"
                label="Debut"
                :value="old('start', optional($event->start)->format('Y-m-d\\TH:i'))"
                required
            />

            <x-ui.input
                id="end"
                type="datetime-local"
                name="end"
                label="Fin"
                :value="old('end', optional($event->end)->format('Y-m-d\\TH:i'))"
                required
            />

            <div>
                <label class="app-label" for="classroom_id">Classe</label>
                <select id="classroom_id" name="classroom_id" class="app-input">
                    <option value="">Aucune classe specifique</option>
                    @foreach($classrooms as $classroom)
                        <option value="{{ $classroom->id }}" @selected((string) old('classroom_id', $event->classroom_id) === (string) $classroom->id)>
                            {{ $classroom->name }}
                        </option>
                    @endforeach
                </select>
                @error('classroom_id')<p class="app-error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="app-label" for="teacher_id">Enseignant</label>
                <select id="teacher_id" name="teacher_id" class="app-input">
                    <option value="">Aucun enseignant specifique</option>
                    @foreach($teachers as $teacher)
                        <option value="{{ $teacher->id }}" @selected((string) old('teacher_id', $event->teacher_id) === (string) $teacher->id)>
                            {{ $teacher->name }}
                        </option>
                    @endforeach
                </select>
                @error('teacher_id')<p class="app-error">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="flex flex-wrap gap-3 border-t border-slate-200 pt-4">
            <x-ui.button type="submit" variant="primary">{{ $submitLabel }}</x-ui.button>
            <x-ui.button :href="route('admin.events.index')" variant="secondary">Retour agenda</x-ui.button>
        </div>
    </form>
</x-ui.card>
