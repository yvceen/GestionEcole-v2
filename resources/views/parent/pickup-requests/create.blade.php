<x-parent-layout title="Nouvelle recuperation" subtitle="Indiquez l'enfant concerne, l'heure prevue et une note optionnelle.">
    <x-ui.card title="Demande de recuperation" subtitle="La vie scolaire recevra votre demande et pourra l'approuver, la rejeter ou la marquer comme traitee.">
        @if($children->isEmpty())
            <div class="student-empty">Aucun enfant n'est rattache a votre compte.</div>
        @else
            <form method="POST" action="{{ route('parent.pickup-requests.store') }}" class="space-y-5" x-data="{ reason: @js(old('reason', '')), useSuggestion(text) { this.reason = text; this.$nextTick(() => this.$refs.reason?.focus()); } }">
                @csrf

                <div class="app-field">
                    <label class="app-label" for="student_id">Enfant</label>
                    <select id="student_id" name="student_id" class="app-input" required>
                        @foreach($children as $child)
                            <option value="{{ $child->id }}" @selected(old('student_id') == $child->id)>
                                {{ $child->full_name }} - {{ $child->classroom?->name ?? '-' }}
                            </option>
                        @endforeach
                    </select>
                    @error('student_id')<p class="app-error">{{ $message }}</p>@enderror
                </div>

                <div class="app-field">
                    <label class="app-label" for="requested_pickup_at">Date et heure prevues</label>
                    <input id="requested_pickup_at" type="datetime-local" name="requested_pickup_at" value="{{ old('requested_pickup_at', now()->addHour()->format('Y-m-d\\TH:i')) }}" class="app-input" required>
                    @error('requested_pickup_at')<p class="app-error">{{ $message }}</p>@enderror
                </div>

                <div class="app-field">
                    <label class="app-label" for="reason">Motif / note optionnelle</label>
                    <div class="mb-2 flex flex-wrap gap-2">
                        @foreach([
                            'Je suis en route',
                            'Je serai à l’école dans 5 minutes',
                            'Préparez mon enfant, s’il vous plaît',
                            'Je suis arrivé devant l’école',
                            'Je passerai plus tard',
                        ] as $suggestion)
                            <button
                                type="button"
                                class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-800"
                                x-on:click="useSuggestion(@js($suggestion))"
                            >
                                {{ $suggestion }}
                            </button>
                        @endforeach
                    </div>
                    <textarea id="reason" name="reason" rows="4" class="app-input" placeholder="Ex: rendez-vous medical, urgence familiale..." x-model="reason" x-ref="reason"></textarea>
                    @error('reason')<p class="app-error">{{ $message }}</p>@enderror
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                    <x-ui.button :href="route('parent.pickup-requests.index')" variant="secondary">
                        Annuler
                    </x-ui.button>
                    <x-ui.button type="submit" variant="primary">
                        Envoyer la demande
                    </x-ui.button>
                </div>
            </form>
        @endif
    </x-ui.card>
</x-parent-layout>
