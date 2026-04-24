<x-school-life-layout title="Corriger un pointage" subtitle="Ajustez une arrivee ou une sortie sans toucher aux autres donnees du portail.">
    <x-ui.page-header
        title="Correction du pointage"
        subtitle="Modification simple pour la vie scolaire : statut, entree, sortie et note."
    />

    <x-ui.card title="{{ $attendance->student?->full_name ?? 'Eleve' }}" subtitle="{{ $attendance->student?->classroom?->name ?? 'Classe non renseignee' }} | {{ $attendance->date?->format('d/m/Y') }}">
        <form method="POST" action="{{ route('school-life.qr-scan.records.update', $attendance) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="app-label" for="status">Statut</label>
                    <select id="status" name="status" class="app-input">
                        @foreach(\App\Models\Attendance::statuses() as $status)
                            <option value="{{ $status }}" @selected(old('status', $attendance->status) === $status)>
                                {{ $status === 'late' ? 'En retard' : ucfirst($status) }}
                            </option>
                        @endforeach
                    </select>
                    @error('status')<p class="app-error">{{ $message }}</p>@enderror
                </div>

                <x-ui.input
                    id="check_in_at"
                    type="datetime-local"
                    name="check_in_at"
                    label="Heure d entree"
                    :value="old('check_in_at', optional($attendance->check_in_at)->format('Y-m-d\\TH:i'))"
                />

                <x-ui.input
                    id="check_out_at"
                    type="datetime-local"
                    name="check_out_at"
                    label="Heure de sortie"
                    :value="old('check_out_at', optional($attendance->check_out_at)->format('Y-m-d\\TH:i'))"
                />

                <div class="md:col-span-2">
                    <label class="app-label" for="note">Note</label>
                    <textarea id="note" name="note" rows="4" class="app-input">{{ old('note', $attendance->note) }}</textarea>
                    @error('note')<p class="app-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="flex flex-wrap gap-3 border-t border-slate-200 pt-4">
                <x-ui.button type="submit" variant="primary">Enregistrer</x-ui.button>
                <x-ui.button :href="route('school-life.qr-scan.index', ['date' => optional($attendance->date)->format('Y-m-d'), 'classroom_id' => $attendance->classroom_id])" variant="secondary">
                    Retour au scan
                </x-ui.button>
            </div>
        </form>
    </x-ui.card>
</x-school-life-layout>
