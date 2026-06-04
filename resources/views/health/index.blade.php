<x-dynamic-component :component="$layoutComponent" title="Santé et urgences" subtitle="Situations actives, informations essentielles et suivi des élèves.">
    <section class="overflow-hidden rounded-[28px] border border-sky-100 bg-gradient-to-br from-sky-50 via-white to-emerald-50 p-6 shadow-sm">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-sky-700">Santé scolaire</p>
                <h1 class="mt-2 text-3xl font-bold text-slate-950">Situations à connaître aujourd'hui</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Retrouvez rapidement les élèves malades, les allergies importantes et les consignes utiles.</p>
            </div>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach([['Actives',$stats['active'],'border-sky-100 bg-sky-50'],['Prioritaires',$stats['urgent'],'border-rose-100 bg-rose-50'],['Allergies',$stats['allergies'],'border-amber-100 bg-amber-50'],['Dossiers',$stats['profiles'],'border-emerald-100 bg-emerald-50']] as [$label,$value,$tone])
                    <div class="min-w-28 rounded-2xl border {{ $tone }} px-4 py-3">
                        <p class="text-xs font-semibold text-slate-500">{{ $label }}</p>
                        <p class="mt-1 text-2xl font-bold text-slate-950">{{ $value }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="mt-5 rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm">
        <form method="GET" class="grid gap-3 md:grid-cols-[minmax(0,1fr)_220px_auto] md:items-end">
            <div><label class="app-label">Recherche</label><input name="q" value="{{ $q }}" class="app-input" placeholder="Élève, parent, maladie ou allergie"></div>
            <div><label class="app-label">Afficher</label><select name="status" class="app-input"><option value="active" @selected($status === 'active')>Situations actives</option><option value="attention" @selected($status === 'attention')>Attention particulière</option><option value="all" @selected($status === 'all')>Tous les élèves</option></select></div>
            <div class="flex gap-2"><x-ui.button type="submit">Filtrer</x-ui.button><x-ui.button :href="route($routePrefix.'.index')" variant="secondary">Effacer</x-ui.button></div>
        </form>
    </section>

    <section class="mt-5 grid gap-4 lg:grid-cols-2">
        @forelse($students as $student)
            @php($report = $student->activeHealthReports->first())
            <a href="{{ route($routePrefix.'.show', $student) }}" class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-sky-200 hover:shadow-md">
                <div class="flex items-start justify-between gap-4">
                    <div><h2 class="text-lg font-bold text-slate-950">{{ $student->full_name }}</h2><p class="mt-1 text-sm text-slate-500">{{ $student->classroom?->name ?? 'Sans classe' }} · {{ $student->parentUser?->name ?? 'Parent non lié' }}</p></div>
                    @if($report)<x-ui.badge :variant="in_array($report->severity, ['high','urgent']) ? 'danger' : 'warning'">{{ ucfirst($report->severity) }}</x-ui.badge>@else<x-ui.badge variant="success">Stable</x-ui.badge>@endif
                </div>
                @if($report)<div class="mt-4 rounded-2xl border border-amber-100 bg-amber-50 p-4"><p class="font-semibold text-amber-950">{{ $report->condition_name }}</p><p class="mt-1 line-clamp-2 text-sm text-amber-800">{{ $report->instructions ?: $report->symptoms ?: 'Aucune consigne supplémentaire.' }}</p></div>@endif
                <div class="mt-4 flex flex-wrap gap-2 text-xs font-semibold text-slate-600">
                    @if($student->healthProfile?->allergies)<span class="rounded-full bg-rose-50 px-3 py-1 text-rose-700">Allergie signalée</span>@endif
                    @if($student->healthProfile?->chronic_conditions)<span class="rounded-full bg-violet-50 px-3 py-1 text-violet-700">Suivi permanent</span>@endif
                    <span class="rounded-full bg-sky-50 px-3 py-1 text-sky-700">Ouvrir le dossier</span>
                </div>
            </a>
        @empty
            <div class="rounded-[24px] border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500 lg:col-span-2">Aucun élève ne correspond aux filtres.</div>
        @endforelse
    </section>
    <div class="mt-5">{{ $students->links() }}</div>
</x-dynamic-component>
