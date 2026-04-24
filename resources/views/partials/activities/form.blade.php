<x-ui.card title="Activite" subtitle="Configurez une activite scolaire avec son planning, son type et ses responsables.">
    <form method="POST" action="{{ $action }}" class="space-y-6">
        @csrf
        @if(($method ?? 'POST') !== 'POST')
            @method($method)
        @endif

        <div class="grid gap-4 md:grid-cols-2">
            <x-ui.input
                name="title"
                label="Titre"
                :value="old('title', $activity->title)"
                required
                class="md:col-span-2"
            />

            <div class="md:col-span-2">
                <label class="app-label" for="description">Description</label>
                <textarea id="description" name="description" rows="4" class="app-input">{{ old('description', $activity->description) }}</textarea>
                @error('description')<p class="app-error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="app-label" for="type">Type</label>
                <select id="type" name="type" class="app-input" required>
                    @foreach($types as $activityType)
                        <option value="{{ $activityType }}" @selected(old('type', $activity->type ?: \App\Models\Activity::TYPE_SPORT) === $activityType)>
                            {{ \App\Models\Activity::labelForType($activityType) }}
                        </option>
                    @endforeach
                </select>
                @error('type')<p class="app-error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="app-label" for="color">Couleur calendrier</label>
                <input
                    id="color"
                    type="color"
                    name="color"
                    value="{{ old('color', $activity->color ?: \App\Models\Activity::defaultColorForType((string) ($activity->type ?: \App\Models\Activity::TYPE_SPORT))) }}"
                    class="h-12 w-full rounded-2xl border border-slate-200 bg-white px-2 py-2"
                >
                @error('color')<p class="app-error">{{ $message }}</p>@enderror
            </div>

            <x-ui.input
                id="start_date"
                type="datetime-local"
                name="start_date"
                label="Debut"
                :value="old('start_date', optional($activity->start_date)->format('Y-m-d\\TH:i'))"
                required
            />

            <x-ui.input
                id="end_date"
                type="datetime-local"
                name="end_date"
                label="Fin"
                :value="old('end_date', optional($activity->end_date)->format('Y-m-d\\TH:i'))"
                required
            />

            <div>
                <label class="app-label" for="classroom_id">Classe (optionnel)</label>
                <select id="classroom_id" name="classroom_id" class="app-input">
                    <option value="">Toutes / multi-classes</option>
                    @foreach($classrooms as $classroom)
                        <option value="{{ $classroom->id }}" @selected((string) old('classroom_id', $activity->classroom_id) === (string) $classroom->id)>
                            {{ $classroom->name }}
                        </option>
                    @endforeach
                </select>
                @error('classroom_id')<p class="app-error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="app-label" for="teacher_id">Enseignant (optionnel)</label>
                <select id="teacher_id" name="teacher_id" class="app-input">
                    <option value="">Non assigne</option>
                    @foreach($teachers as $teacher)
                        <option value="{{ $teacher->id }}" @selected((string) old('teacher_id', $activity->teacher_id) === (string) $teacher->id)>
                            {{ $teacher->name }}
                        </option>
                    @endforeach
                </select>
                @error('teacher_id')<p class="app-error">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="flex flex-wrap gap-3 border-t border-slate-200 pt-4">
            <x-ui.button type="submit" variant="primary">{{ $submitLabel }}</x-ui.button>
            <x-ui.button :href="route('admin.activities.index')" variant="secondary">Retour</x-ui.button>
        </div>
    </form>
</x-ui.card>
