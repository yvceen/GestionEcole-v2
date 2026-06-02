<x-admin-layout title="Agenda hebdomadaire" subtitle="Organisez les cours, examens et activités avec une vue claire et rapide.">
    <section class="relative overflow-hidden rounded-[34px] border border-sky-100 bg-gradient-to-br from-slate-950 via-sky-900 to-cyan-700 px-6 py-7 text-white shadow-2xl shadow-sky-100 md:px-8">
        <div class="absolute right-8 top-8 h-28 w-28 rounded-full bg-white/10 blur-2xl"></div>
        <div class="relative flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
            <div class="max-w-3xl">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-cyan-100">Organisation</p>
                <h1 class="mt-3 text-3xl font-semibold tracking-tight md:text-4xl">Agenda de l'Établissement</h1>
                <p class="mt-3 max-w-2xl text-sm leading-7 text-sky-100">
                    Visualisez la semaine, filtrez par classe ou enseignant, et gerez les blocs importants depuis une interface plus lisible.
                </p>
            </div>
            <div class="flex flex-wrap gap-3">
                @if($canManage)
                    <x-ui.button :href="route('admin.events.create')" variant="secondary">Ajouter un bloc</x-ui.button>
                @endif
                <x-ui.button :href="route('admin.activities.index')" variant="ghost">Activités</x-ui.button>
            </div>
        </div>
    </section>

    @include('partials.events.week-calendar')
</x-admin-layout>
