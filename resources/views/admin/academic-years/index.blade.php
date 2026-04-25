<x-admin-layout title="Annees scolaires" subtitle="Gerez les annees scolaires actives, futures et archivees sans perdre l historique.">
    <x-ui.page-header title="Annees scolaires" subtitle="Chaque ecole garde son historique et travaille par annee active.">
        <x-slot name="actions">
            <x-ui.button :href="route('admin.academic-years.create')" variant="primary">Nouvelle annee</x-ui.button>
            <x-ui.button :href="route('admin.academic-promotions.index')" variant="secondary">Passage a l annee suivante</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    @if($errors->has('academic_year'))
        <x-ui.alert variant="danger">{{ $errors->first('academic_year') }}</x-ui.alert>
    @endif

    <x-ui.card title="Annee courante" subtitle="L annee affichee par defaut sur les tableaux de bord et ecrans mobiles.">
        <div class="flex flex-wrap items-center gap-3">
            <x-ui.badge variant="success">{{ $currentAcademicYear->name }}</x-ui.badge>
            <span class="text-sm text-slate-600">{{ $currentAcademicYear->starts_at?->format('d/m/Y') }} - {{ $currentAcademicYear->ends_at?->format('d/m/Y') }}</span>
            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">{{ ucfirst((string) $currentAcademicYear->status) }}</span>
        </div>
    </x-ui.card>

    <x-ui.card title="Liste des annees" subtitle="Archivez les annees cloturees et activez la bonne annee au bon moment.">
        <div class="overflow-x-auto rounded-2xl border border-slate-200">
            <table class="app-table min-w-[760px]">
                <thead>
                    <tr>
                        <th>Annee</th>
                        <th>Periode</th>
                        <th>Statut</th>
                        <th>Courante</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($years as $year)
                        <tr>
                            <td class="font-semibold text-slate-900">{{ $year->name }}</td>
                            <td>{{ $year->starts_at?->format('d/m/Y') }} - {{ $year->ends_at?->format('d/m/Y') }}</td>
                            <td><x-ui.badge :variant="$year->status === 'archived' ? 'secondary' : ($year->status === 'active' ? 'success' : 'warning')">{{ ucfirst((string) $year->status) }}</x-ui.badge></td>
                            <td>
                                @if($year->is_current)
                                    <x-ui.badge variant="success">Oui</x-ui.badge>
                                @else
                                    <span class="text-sm text-slate-500">Non</span>
                                @endif
                            </td>
                            <td>
                                <div class="flex flex-wrap gap-2">
                                    @if(!$year->is_current)
                                        <form method="POST" action="{{ route('admin.academic-years.activate', $year) }}">
                                            @csrf
                                            <x-ui.button type="submit" size="sm" variant="primary">Activer</x-ui.button>
                                        </form>
                                    @endif
                                    @if($year->status !== 'archived')
                                        <form method="POST" action="{{ route('admin.academic-years.archive', $year) }}" onsubmit="return confirm('Archiver cette annee scolaire ?')">
                                            @csrf
                                            <x-ui.button type="submit" size="sm" variant="secondary">Archiver</x-ui.button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-sm text-slate-500">Aucune annee scolaire n est encore configuree.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
</x-admin-layout>
