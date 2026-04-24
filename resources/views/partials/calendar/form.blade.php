<x-ui.card title="Evenement calendrier" subtitle="Renseignez un titre, une periode et un type pour le rendre lisible dans tous les portails.">
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
                    @foreach($types as $calendarType)
                        <option value="{{ $calendarType }}" @selected(old('type', $event->type ?: \App\Models\SchoolCalendarEvent::TYPE_EVENT) === $calendarType)>
                            {{ \App\Models\SchoolCalendarEvent::labelForType($calendarType) }}
                        </option>
                    @endforeach
                </select>
                @error('type')<p class="app-error">{{ $message }}</p>@enderror
            </div>

            <x-ui.input
                id="starts_on"
                type="date"
                name="starts_on"
                label="Debut"
                :value="old('starts_on', optional($event->starts_on)->format('Y-m-d'))"
                required
            />

            <x-ui.input
                id="ends_on"
                type="date"
                name="ends_on"
                label="Fin"
                :value="old('ends_on', optional($event->ends_on)->format('Y-m-d'))"
            />

            <div class="md:col-span-2">
                <label class="app-label" for="description">Description</label>
                <textarea id="description" name="description" rows="5" class="app-input">{{ old('description', $event->description) }}</textarea>
                @error('description')<p class="app-error">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="flex flex-wrap gap-3 border-t border-slate-200 pt-4">
            <x-ui.button type="submit" variant="primary">{{ $submitLabel }}</x-ui.button>
            <x-ui.button :href="route('admin.calendar.index')" variant="secondary">Retour calendrier</x-ui.button>
        </div>
    </form>
</x-ui.card>
