<x-admin-layout title="Detail route transport">
    <x-ui.page-header :title="$route->route_name" :subtitle="$route->start_point.' -> '.$route->end_point">
        <x-slot name="actions">
            <x-ui.button :href="route('admin.transport.routes.edit', $route)" variant="primary">Modifier</x-ui.button>
            <x-ui.button :href="route('admin.transport.routes.index')" variant="secondary">Retour</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    @if(session('success'))
        <x-ui.alert variant="success">{{ session('success') }}</x-ui.alert>
    @endif

    @if($errors->any())
        <x-ui.alert variant="error">
            <ul class="space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-ui.alert>
    @endif

    <section class="grid gap-6 xl:grid-cols-[minmax(0,1.4fr)_380px]">
        <div class="space-y-6">
            <x-ui.card title="Synthese circuit" subtitle="Vehicule, conducteur, arrets et eleves actifs.">
                <div class="grid gap-4 md:grid-cols-2 text-sm">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <p class="text-slate-500">Vehicule</p>
                        <p class="font-semibold text-slate-900">{{ $route->vehicle?->name ?: ($route->vehicle?->registration_number ?? 'Non assigne') }}</p>
                        <p class="mt-1 text-xs text-slate-500">Conducteur : {{ $route->vehicle?->driver?->name ?? 'Non renseigne' }}{{ $route->vehicle?->driver?->phone ? ' • '.$route->vehicle->driver->phone : '' }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <p class="text-slate-500">Volume</p>
                        <p class="font-semibold text-slate-900">{{ $route->assignments->where('is_active', true)->count() }} eleve(s) actifs</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $route->stops->count() }} arret(s) • {{ $route->estimated_minutes ?? '-' }} min</p>
                    </div>
                </div>

                <div class="mt-4 space-y-2">
                    @forelse($route->stops->sortBy('stop_order') as $stop)
                        <div class="rounded-2xl border border-slate-200 px-4 py-3 text-sm">
                            <div class="flex items-center justify-between gap-3">
                                <p class="font-semibold text-slate-900">{{ $stop->stop_order }}. {{ $stop->name }}</p>
                                <span class="text-xs text-slate-500">{{ $stop->scheduled_time ? substr((string) $stop->scheduled_time, 0, 5) : 'Horaire libre' }}</span>
                            </div>
                            @if($stop->notes)
                                <p class="mt-1 text-xs text-slate-500">{{ $stop->notes }}</p>
                            @endif
                        </div>
                    @empty
                        <div class="student-empty">Aucun arret configure pour cette route.</div>
                    @endforelse
                </div>
            </x-ui.card>

            <x-ui.card title="Eleves affectes" subtitle="Liste active des eleves relies a cette route.">
                <div class="space-y-3">
                    @forelse($route->assignments->where('is_active', true) as $assignment)
                        <article class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $assignment->student?->full_name ?? '-' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $assignment->student?->classroom?->name ?? '-' }} • {{ ucfirst((string) ($assignment->period ?? 'both')) }}</p>
                                </div>
                                <span class="text-xs text-slate-500">{{ $assignment->pickup_point ?: 'Point non precise' }}</span>
                            </div>
                        </article>
                    @empty
                        <div class="student-empty">Aucun eleve actif sur cette route.</div>
                    @endforelse
                </div>
            </x-ui.card>
        </div>

        <div class="space-y-6">
            <x-ui.card title="Affecter des eleves" subtitle="Ajoutez une classe complete ou une selection precise sans quitter la route.">
                <form method="POST" action="{{ route('admin.transport.routes.assign-students', $route) }}" class="space-y-4">
                    @csrf
                    <x-ui.select label="Classe complete (optionnel)" name="classroom_id">
                        <option value="">Choisir une classe</option>
                        @foreach($classrooms as $classroom)
                            <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                        @endforeach
                    </x-ui.select>

                    <div>
                        <label class="app-label">Eleves complementaires</label>
                        <div class="max-h-56 space-y-2 overflow-y-auto rounded-2xl border border-slate-200 bg-slate-50 p-3">
                            @foreach(\App\Models\Student::query()->where('school_id', app('current_school_id'))->active()->orderBy('full_name')->get(['id', 'full_name']) as $student)
                                <label class="flex items-center gap-2 text-sm text-slate-700">
                                    <input type="checkbox" name="student_ids[]" value="{{ $student->id }}" class="rounded border-slate-300">
                                    <span>{{ $student->full_name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <x-ui.select label="Periode" name="period">
                        <option value="both">Matin et soir</option>
                        <option value="morning">Matin</option>
                        <option value="evening">Soir</option>
                    </x-ui.select>
                    <x-ui.input label="Point de ramassage commun" name="pickup_point" :value="old('pickup_point')" />
                    <x-ui.input label="Date d affectation" type="date" name="assigned_date" :value="old('assigned_date', now()->toDateString())" required />

                    <x-ui.button type="submit" variant="primary">Enregistrer les affectations</x-ui.button>
                </form>
            </x-ui.card>
        </div>
    </section>
</x-admin-layout>
