@php
    $selectedClassrooms = collect(old('classroom_ids', $event?->targets?->pluck('student.classroom_id')->filter()->unique()->values()->all() ?? []))->map(fn ($id) => (string) $id)->all();
    $selectedStudents = collect(old('student_ids', $event?->targets?->pluck('student_id')->all() ?? []))->map(fn ($id) => (string) $id)->all();
    $scope = old('target_scope', $event ? 'students' : 'school');
@endphp

<x-dynamic-component :component="$layoutComponent" title="{{ $event ? 'Modifier evenement' : 'Nouvel evenement' }}">
    <section class="mx-auto max-w-5xl overflow-hidden rounded-[32px] border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 bg-[radial-gradient(circle_at_top_right,_rgba(14,165,233,0.16),_transparent_34%),linear-gradient(135deg,#f8fafc,#ffffff)] px-6 py-6">
            <p class="app-overline">Paiements evenements</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">{{ $event ? 'Modifier evenement' : 'Nouvel evenement payant' }}</h1>
            <p class="mt-2 text-sm text-slate-500">Choisissez le montant et les eleves concernes.</p>
        </div>

        @if($errors->any())
            <x-ui.alert variant="error" class="m-6">
                <ul class="list-disc pl-4">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-ui.alert>
        @endif

        <form method="POST" action="{{ $action }}" class="space-y-6 px-6 py-6">
            @csrf
            @if($method !== 'POST')
                @method($method)
            @endif

            <div class="grid gap-5 md:grid-cols-2">
                <x-ui.input name="title" label="Nom evenement" :value="old('title', $event?->title)" placeholder="Fete de fin d'annee" />
                <x-ui.input name="amount_per_student" label="Montant par eleve (MAD)" type="number" step="0.01" min="0" :value="old('amount_per_student', $event?->amount_per_student)" />
                <x-ui.input name="event_date" label="Date evenement" type="date" :value="old('event_date', optional($event?->event_date)->format('Y-m-d'))" />
                <x-ui.input name="due_date" label="Date limite paiement" type="date" :value="old('due_date', optional($event?->due_date)->format('Y-m-d'))" />
            </div>

            <x-ui.textarea name="description" label="Description" rows="4" placeholder="Details utiles pour l'administration...">{{ old('description', $event?->description) }}</x-ui.textarea>

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-semibold">Statut</label>
                    <select name="status" class="app-input">
                        <option value="active" @selected(old('status', $event?->status ?? 'active') === 'active')>Actif</option>
                        <option value="closed" @selected(old('status', $event?->status) === 'closed')>Cloture</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold">Cible</label>
                    <select id="target_scope" name="target_scope" class="app-input">
                        <option value="school" @selected($scope === 'school')>Toute l'ecole</option>
                        <option value="classrooms" @selected($scope === 'classrooms')>Classes</option>
                        <option value="students" @selected($scope === 'students')>Eleves precis</option>
                    </select>
                </div>
            </div>

            <div id="classrooms_block" class="hidden rounded-2xl border border-sky-100 bg-sky-50/60 px-5 py-5">
                <p class="text-sm font-semibold text-slate-950">Classes concernees</p>
                <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($classrooms as $classroom)
                        <label class="flex items-center gap-3 rounded-2xl bg-white px-4 py-3 text-sm font-semibold text-slate-700 ring-1 ring-slate-200">
                            <input type="checkbox" name="classroom_ids[]" value="{{ $classroom->id }}" @checked(in_array((string) $classroom->id, $selectedClassrooms, true))>
                            {{ $classroom->name }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div id="students_block" class="hidden rounded-2xl border border-emerald-100 bg-emerald-50/60 px-5 py-5">
                <p class="text-sm font-semibold text-slate-950">Eleves concernes</p>
                <div class="mt-4 max-h-[24rem] space-y-2 overflow-auto rounded-2xl bg-white p-3 ring-1 ring-slate-200">
                    @foreach($students as $student)
                        <label class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm transition hover:bg-slate-50">
                            <input type="checkbox" name="student_ids[]" value="{{ $student->id }}" @checked(in_array((string) $student->id, $selectedStudents, true))>
                            <span class="min-w-0">
                                <span class="block font-semibold text-slate-900">{{ $student->full_name }}</span>
                                <span class="block text-xs text-slate-500">{{ $student->classroom?->name ?? '-' }} | {{ $student->parentUser?->name ?? 'Parent non lie' }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <x-ui.button :href="route($routePrefix . '.index')" variant="secondary">Annuler</x-ui.button>
                <x-ui.button type="submit" variant="primary">{{ $submitLabel }}</x-ui.button>
            </div>
        </form>
    </section>

    <script>
        (function () {
            const scope = document.getElementById('target_scope');
            const classrooms = document.getElementById('classrooms_block');
            const students = document.getElementById('students_block');

            function sync() {
                classrooms.classList.toggle('hidden', scope.value !== 'classrooms');
                students.classList.toggle('hidden', scope.value !== 'students');
            }

            scope.addEventListener('change', sync);
            sync();
        })();
    </script>
</x-dynamic-component>
