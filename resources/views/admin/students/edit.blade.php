<x-admin-layout title="Modifier un Élève">
    <x-students.header
        title="Modifier un Élève"
        subtitle="Mettez a jour les informations de l'Élève sans modifier la logique metier."
    >
        <x-ui.button :href="route('admin.students.index')" variant="ghost">
            Retour
        </x-ui.button>
    </x-students.header>

    <x-students.form
        mode="edit"
        :action="route('admin.students.update', $student)"
        method="PUT"
        :student="$student"
        :classrooms="$classrooms"
        :parents="$parents"
        :routes="($routes ?? collect())"
        :vehicles="($vehicles ?? collect())"
        :transport-assignment="($transportAssignment ?? null)"
    />

    @if($errors->has('delete_student'))
        <div class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-900">
            {{ $errors->first('delete_student') }}
        </div>
    @endif

    <x-ui.card class="mt-6" title="Cycle de vie" subtitle="Archivez un Élève qui ne frequente plus l'École sans supprimer son historique.">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                @if($student->is_archived)
                    <span class="app-badge app-badge-warning">Archive</span>
                    <p class="mt-2 text-sm text-slate-600">
                        Archive le {{ $student->archived_at?->format('d/m/Y H:i') }}.
                        @if($student->archive_reason)
                            Motif: {{ $student->archive_reason }}
                        @endif
                    </p>
                @else
                    <span class="app-badge app-badge-success">Actif</span>
                    <p class="mt-2 text-sm text-slate-600">L'Élève apparait dans les listes actives et les workflows operationnels.</p>
                @endif
            </div>

            @if($student->is_archived)
                <form method="POST" action="{{ route('admin.students.reactivate', $student) }}">
                    @csrf
                    <x-ui.button type="submit" variant="secondary">Reactiver</x-ui.button>
                </form>
            @else
                <form method="POST" action="{{ route('admin.students.archive', $student) }}" class="w-full space-y-3 sm:max-w-sm" onsubmit="return confirm('Archiver cet Élève ? Son historique sera conserve.')">
                    @csrf
                    <textarea name="archive_reason" rows="2" class="app-input" placeholder="Motif optionnel: depart, transfert...">{{ old('archive_reason') }}</textarea>
                    @error('archive_reason')<p class="app-error">{{ $message }}</p>@enderror
                    <x-ui.button type="submit" variant="secondary">Archiver cet Élève</x-ui.button>
                </form>
            @endif
        </div>
    </x-ui.card>

    <x-ui.card class="mt-6 border-rose-200 bg-rose-50/70" title="Zone sensible" subtitle="La suppression est disponible uniquement si aucun dossier operationnel n'est lié a cet Élève.">
        <form method="POST" action="{{ route('admin.students.destroy', $student) }}" onsubmit="return confirm('Supprimer cet Élève ? Cette action sera refusée si des données liees existent.')">
            @csrf
            @method('DELETE')
            <x-ui.button type="submit" variant="danger">
                Supprimer cet Élève
            </x-ui.button>
        </form>
    </x-ui.card>
</x-admin-layout>
