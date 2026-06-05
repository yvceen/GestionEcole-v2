<x-parent-layout title="Demander un rendez-vous" subtitle="Envoyez une demande claire a l'administration et suivez la reponse depuis votre espace parent.">
    <section class="student-panel">
        @if($errors->any())
            <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                <ul class="ml-5 list-disc">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('parent.appointments.store') }}" class="app-form-stack" data-loading-label="Envoi de la demande...">
            @csrf

            <div class="grid gap-4 md:grid-cols-2">
                <div class="app-field md:col-span-2">
                    <label class="app-label" for="title">Titre</label>
                    <input
                        id="title"
                        name="title"
                        value="{{ old('title') }}"
                        placeholder="Ex: Suivi pédagogique"
                        class="app-input"
                    >
                </div>

                <div class="app-field">
                    <label class="app-label" for="scheduled_at">Date et heure souhaitees</label>
                    <input
                        id="scheduled_at"
                        type="datetime-local"
                        name="scheduled_at"
                        value="{{ old('scheduled_at') }}"
                        class="app-input"
                    >
                </div>

                @if(($children ?? collect())->isNotEmpty())
                    <div class="app-field">
                        <label class="app-label" for="student_id">Enfant concerne</label>
                        <select id="student_id" name="student_id" class="app-input">
                            <option value="">Aucun enfant specifique</option>
                            @foreach($children as $child)
                                <option value="{{ $child->id }}" @selected((int) old('student_id') === (int) $child->id)>
                                    {{ $child->full_name }}{{ $child->classroom?->name ? ' - ' . $child->classroom->name : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="app-field md:col-span-2">
                    <label class="app-label" for="parent_phone">Telephone optionnel</label>
                    <input
                        id="parent_phone"
                        name="parent_phone"
                        value="{{ old('parent_phone') }}"
                        placeholder="06 xx xx xx xx"
                        class="app-input"
                    >
                </div>

                <div class="app-field md:col-span-2">
                    <label class="app-label" for="message">Message / details</label>
                    <textarea
                        id="message"
                        name="message"
                        rows="4"
                        placeholder="Expliquez brievement votre demande..."
                        class="app-input"
                    >{{ old('message') }}</textarea>
                </div>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                <x-ui.button :href="route('parent.appointments.index')" variant="secondary">
                    Annuler
                </x-ui.button>
                <x-ui.button type="submit" variant="primary">
                    Envoyer
                </x-ui.button>
            </div>
        </form>
    </section>
</x-parent-layout>
