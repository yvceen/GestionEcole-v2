@php
    $selectedStudentId = old('student_id', $appointment?->student_id);
    $scheduledAtValue = old('scheduled_at', optional($appointment?->scheduled_for)->format('Y-m-d\TH:i'));
@endphp

@if($errors->any())
    <div class="rounded-3xl border border-red-200 bg-red-50 p-4 text-red-900">
        <ul class="ml-5 list-disc">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid gap-6 xl:grid-cols-[minmax(0,1.2fr)_minmax(320px,0.9fr)]">
    <x-ui.card title="Demande" subtitle="Informations visibles dans la liste et cote parent.">
        <div class="grid gap-5 md:grid-cols-2">
            <x-ui.input label="Titre" name="title" :value="old('title', $appointment?->title)" />
            <x-ui.input label="Telephone parent" name="parent_phone" :value="old('parent_phone', $appointment?->parent_phone)" />
            <x-ui.input label="Date et heure" name="scheduled_at" type="datetime-local" :value="$scheduledAtValue" />

            <div>
                <label class="app-label" for="student_id">Enfant concerne</label>
                <select id="student_id" name="student_id" class="app-input">
                    <option value="">Aucun enfant specifique</option>
                    @foreach($children as $child)
                        <option value="{{ $child->id }}" @selected((int) $selectedStudentId === (int) $child->id)>
                            {{ $child->full_name }}{{ $child->classroom?->name ? ' - ' . $child->classroom->name : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-2">
                <label class="app-label" for="message">Message parent / contexte</label>
                <textarea id="message" name="message" rows="5" class="app-input">{{ old('message', $appointment?->message) }}</textarea>
            </div>
        </div>
    </x-ui.card>

    <x-ui.card title="Traitement interne" subtitle="Suivi administratif et workflow.">
        <div class="space-y-5">
            <div>
                <label class="app-label" for="status">Statut</label>
                <select id="status" name="status" class="app-input">
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $appointment?->normalized_status ?? 'pending') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="app-label" for="admin_notes">Notes administration</label>
                <textarea id="admin_notes" name="admin_notes" rows="6" class="app-input">{{ old('admin_notes', $appointment?->admin_notes) }}</textarea>
            </div>
        </div>
    </x-ui.card>
</div>

<div class="flex justify-end gap-3">
    <x-ui.button :href="route('admin.appointments.index')" variant="secondary">Annuler</x-ui.button>
    <x-ui.button type="submit" variant="primary">{{ $appointment ? 'Enregistrer' : 'Creer' }}</x-ui.button>
</div>
