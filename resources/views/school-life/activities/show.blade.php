<x-school-life-layout :title="$activity->title" subtitle="Suivi des participants, pointage et compte rendu de l activite.">
    <x-ui.page-header :title="$activity->title" :subtitle="($activity->start_date?->format('d/m/Y H:i') ?? '-') . ' -> ' . ($activity->end_date?->format('d/m/Y H:i') ?? '-')">
        <x-slot name="actions">
            <x-ui.button :href="route('school-life.activities.index')" variant="secondary">Retour liste</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <section class="grid gap-4 md:grid-cols-3">
        <article class="app-stat-card">
            <p class="app-stat-label">Participants</p>
            <p class="app-stat-value">{{ $activity->participants->count() }}</p>
        </article>
        <article class="app-stat-card">
            <p class="app-stat-label">Confirmes</p>
            <p class="app-stat-value text-emerald-700">{{ $activity->participants->where('confirmation_status', \App\Models\ActivityParticipant::CONFIRMATION_CONFIRMED)->count() }}</p>
        </article>
        <article class="app-stat-card">
            <p class="app-stat-label">Presents</p>
            <p class="app-stat-value text-sky-700">{{ $activity->participants->where('attendance_status', \App\Models\ActivityParticipant::ATTENDANCE_PRESENT)->count() }}</p>
        </article>
    </section>

    <x-ui.card title="Participants" subtitle="Mettez a jour la confirmation et la presence de chaque eleve.">
        <div class="space-y-3">
            @forelse($activity->participants as $participant)
                <form method="POST" action="{{ route('school-life.activities.participants.update', [$activity, $participant]) }}" class="rounded-2xl border border-slate-200 bg-white px-4 py-4">
                    @csrf
                    <div class="grid gap-3 lg:grid-cols-[minmax(0,1.3fr)_180px_180px_minmax(0,1fr)_auto] lg:items-end">
                        <div>
                            <p class="font-semibold text-slate-900">{{ $participant->student?->full_name ?? '-' }}</p>
                            <p class="text-xs text-slate-500">{{ $participant->student?->classroom?->name ?? '-' }} | Parent: {{ $participant->student?->parentUser?->name ?? '-' }}</p>
                        </div>
                        <select name="confirmation_status" class="app-input">
                            <option value="pending" @selected($participant->confirmation_status === 'pending')>En attente</option>
                            <option value="confirmed" @selected($participant->confirmation_status === 'confirmed')>Confirme</option>
                            <option value="declined" @selected($participant->confirmation_status === 'declined')>Refuse</option>
                        </select>
                        <select name="attendance_status" class="app-input">
                            <option value="">Presence non renseignee</option>
                            <option value="present" @selected($participant->attendance_status === 'present')>Present</option>
                            <option value="absent" @selected($participant->attendance_status === 'absent')>Absent</option>
                        </select>
                        <input type="text" name="note" class="app-input" placeholder="Note rapide" value="{{ $participant->note }}">
                        <x-ui.button type="submit" variant="primary" size="sm">Enregistrer</x-ui.button>
                    </div>
                </form>
            @empty
                <div class="student-empty">Aucun participant pour cette activite.</div>
            @endforelse
        </div>
    </x-ui.card>

    <section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
        <x-ui.card title="Comptes rendus" subtitle="Historique des retours terrains saisis par la vie scolaire.">
            <div class="space-y-3">
                @forelse($activity->reports as $report)
                    <article class="rounded-2xl border border-slate-200 bg-white px-4 py-4">
                        <p class="text-sm text-slate-500">{{ $report->created_at?->format('d/m/Y H:i') }} - {{ $report->author?->name ?? '-' }}</p>
                        <p class="mt-2 text-sm text-slate-700">{{ $report->report_text }}</p>
                        @if($report->image_path)
                            <a href="{{ asset('storage/' . ltrim($report->image_path, '/')) }}" target="_blank" class="mt-3 inline-block text-sm font-semibold text-sky-700 hover:text-sky-800">
                                Voir image
                            </a>
                        @endif
                    </article>
                @empty
                    <div class="student-empty">Aucun rapport pour cette activite.</div>
                @endforelse
            </div>
        </x-ui.card>

        <x-ui.card title="Ajouter un rapport" subtitle="Ajoutez un texte et une photo (optionnelle).">
            <form method="POST" action="{{ route('school-life.activities.reports.store', $activity) }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label class="app-label" for="report_text">Compte rendu</label>
                    <textarea id="report_text" name="report_text" rows="5" class="app-input" required>{{ old('report_text') }}</textarea>
                </div>
                <div>
                    <label class="app-label" for="image">Image (optionnel)</label>
                    <input id="image" type="file" name="image" class="app-input" accept="image/*">
                </div>
                <x-ui.button type="submit" variant="primary">Ajouter rapport</x-ui.button>
            </form>
        </x-ui.card>
    </section>
</x-school-life-layout>
