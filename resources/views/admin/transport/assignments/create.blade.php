<x-admin-layout title="Nouvelle affectation">
    @php
        $selectedStudentIds = collect(old('student_ids', []))->map(fn ($id) => (int) $id)->all();
    @endphp

    <x-ui.page-header
        title="Nouvelle affectation"
        subtitle="Affectez un Élève, plusieurs Élèves ou toute une classe a une route de transport."
    >
        <x-slot name="actions">
            <x-ui.button :href="route('admin.transport.assignments.index')" variant="secondary">
                Retour
            </x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.25fr)_360px]">
        <x-ui.card title="Affectation transport" subtitle="Sélection simple, route unique, affectation rapide.">
            <form method="POST" action="{{ route('admin.transport.assignments.store') }}" class="space-y-5">
                @csrf

                <div class="rounded-3xl border border-sky-100 bg-sky-50/70 p-4">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.18em] text-sky-700">1. Élèves</p>
                            <p class="mt-1 text-sm text-slate-600">Choisissez une classe complete ou cochez les Élèves manuellement.</p>
                        </div>
                        <span id="selectedStudentsCount" class="rounded-full bg-white px-3 py-1 text-xs font-bold text-sky-700 shadow-sm">0 sélection</span>
                    </div>

                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <div class="app-field">
                            <label class="app-label">Classe complete</label>
                            <select name="classroom_id" id="classroomFilter" class="app-input @error('classroom_id') border-rose-500 @enderror">
                                <option value="">Aucune classe complete</option>
                                @foreach(($classrooms ?? collect()) as $classroom)
                                    <option value="{{ $classroom->id }}" @selected(old('classroom_id') == $classroom->id)>{{ $classroom->name }}</option>
                                @endforeach
                            </select>
                            @error('classroom_id')<p class="app-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="app-field">
                            <label class="app-label">Recherche Élève</label>
                            <input type="search" id="studentSearch" class="app-input" placeholder="Nom, classe...">
                        </div>
                    </div>

                    @error('student_id')<p class="mt-3 app-error">{{ $message }}</p>@enderror
                    @error('student_ids')<p class="mt-3 app-error">{{ $message }}</p>@enderror

                    <div class="mt-4 max-h-72 overflow-y-auto rounded-2xl border border-sky-100 bg-white p-3">
                        <div class="grid gap-2 md:grid-cols-2">
                            @foreach(($students ?? collect()) as $student)
                                <label class="transport-student-option flex cursor-pointer items-center gap-3 rounded-2xl border border-slate-200 bg-white px-3 py-3 transition hover:border-sky-300 hover:bg-sky-50/60"
                                       data-name="{{ strtolower($student->full_name.' '.$student->classroom?->name) }}"
                                       data-classroom="{{ $student->classroom_id }}">
                                    <input type="checkbox" name="student_ids[]" value="{{ $student->id }}" class="transport-student-checkbox rounded border-slate-300 text-sky-700" @checked(in_array((int) $student->id, $selectedStudentIds, true))>
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-semibold text-slate-900">{{ $student->full_name }}</span>
                                        <span class="block text-xs text-slate-500">{{ $student->classroom?->name ?? '-' }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-indigo-100 bg-indigo-50/70 p-4">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-indigo-700">2. Circuit</p>
                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <div class="app-field">
                            <label class="app-label">Vehicule</label>
                            <select id="vehicle_id" name="vehicle_id" class="app-input @error('vehicle_id') border-rose-500 @enderror">
                                <option value="">Auto depuis la route</option>
                                @foreach(($vehicles ?? collect()) as $vehicle)
                                    <option value="{{ $vehicle->id }}" @selected(old('vehicle_id') == $vehicle->id)>
                                        {{ $vehicle->registration_number }} | Chauffeur : {{ $vehicle->driver?->name ?? '-' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('vehicle_id')<p class="app-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="app-field">
                            <label class="app-label">Route</label>
                            <select id="route_id" name="route_id" class="app-input @error('route_id') border-rose-500 @enderror" required>
                                <option value="">Selectionner une route</option>
                                @foreach(($routes ?? collect()) as $route)
                                    <option value="{{ $route->id }}" data-vehicle="{{ $route->vehicle_id }}" @selected(old('route_id') == $route->id)>
                                        {{ $route->route_name }} ({{ $route->start_point }} -> {{ $route->end_point }})
                                    </option>
                                @endforeach
                            </select>
                            @error('route_id')<p class="app-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="app-field">
                        <label class="app-label">Période</label>
                        <select name="period" class="app-input @error('period') border-rose-500 @enderror" required>
                            <option value="both" @selected(old('period', 'both') == 'both')>Matin et soir</option>
                            <option value="morning" @selected(old('period') == 'morning')>Matin</option>
                            <option value="evening" @selected(old('period') == 'evening')>Soir</option>
                        </select>
                        @error('period')<p class="app-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="app-field">
                        <label class="app-label">Date de début</label>
                        <input type="date" name="assigned_date" value="{{ old('assigned_date', date('Y-m-d')) }}" class="app-input @error('assigned_date') border-rose-500 @enderror" required>
                        @error('assigned_date')<p class="app-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="app-field">
                    <label class="app-label">Point de ramassage</label>
                    <input type="text" name="pickup_point" value="{{ old('pickup_point') }}" class="app-input @error('pickup_point') border-rose-500 @enderror" placeholder="Ex. : Pres du stade municipal">
                    @error('pickup_point')<p class="app-error">{{ $message }}</p>@enderror
                </div>

                <div class="flex items-center gap-3">
                    <x-ui.button type="submit" variant="primary">Enregistrer les affectations</x-ui.button>
                    <x-ui.button :href="route('admin.transport.assignments.index')" variant="secondary">Annuler</x-ui.button>
                </div>
            </form>
        </x-ui.card>

        <div class="space-y-4">
            <div class="rounded-3xl border border-emerald-100 bg-gradient-to-br from-emerald-50 to-white p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">Mode rapide</p>
                <h3 class="mt-3 text-xl font-bold text-slate-950">Affectation par classe</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">Sélectionnez une classe, une route et une date. Tous les Élèves actifs de cette classe seront reliés au transport.</p>
            </div>
            <div class="rounded-3xl border border-amber-100 bg-gradient-to-br from-amber-50 to-white p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-amber-700">Tarifs</p>
                <p class="mt-3 text-sm leading-6 text-slate-600">Les montants transport restent geres dans la partie finance ou fiche Élève. Ce formulaire sert uniquement aux trajets et affectations.</p>
            </div>
        </div>
    </div>

    <script>
        const vehicleSelect = document.getElementById('vehicle_id');
        const routeSelect = document.getElementById('route_id');

        function filterRoutes() {
            const vehicleId = vehicleSelect.value;

            [...routeSelect.options].forEach(option => {
                if (!option.value) return;
                const routeVehicleId = option.getAttribute('data-vehicle');
                option.hidden = (vehicleId && routeVehicleId && routeVehicleId !== vehicleId);
            });

            if (routeSelect.selectedOptions.length && routeSelect.selectedOptions[0].hidden) {
                routeSelect.value = '';
            }
        }

        vehicleSelect?.addEventListener('change', filterRoutes);
        filterRoutes();

        const classroomFilter = document.getElementById('classroomFilter');
        const studentSearch = document.getElementById('studentSearch');
        const studentOptions = [...document.querySelectorAll('.transport-student-option')];
        const studentCheckboxes = [...document.querySelectorAll('.transport-student-checkbox')];
        const selectedStudentsCount = document.getElementById('selectedStudentsCount');

        function updateStudentList() {
            const q = (studentSearch?.value || '').trim().toLowerCase();
            const classroomId = classroomFilter?.value || '';
            let visible = 0;

            studentOptions.forEach(option => {
                const matchesSearch = !q || option.dataset.name.includes(q);
                const matchesClass = !classroomId || option.dataset.classroom === classroomId;
                const show = matchesSearch && matchesClass;
                option.classList.toggle('hidden', !show);
                if (show) visible++;
            });

            const checked = studentCheckboxes.filter(input => input.checked).length;
            selectedStudentsCount.textContent = checked ? `${checked} sélection` : `${visible} visible`;
        }

        classroomFilter?.addEventListener('change', updateStudentList);
        studentSearch?.addEventListener('input', updateStudentList);
        studentCheckboxes.forEach(input => input.addEventListener('change', updateStudentList));
        updateStudentList();
    </script>
</x-admin-layout>
