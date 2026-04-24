<x-super-layout
    title="Gestion des ecoles"
    subtitle="Parcourez les etablissements, filtrez rapidement les resultats et lancez les actions de gestion sans quitter la liste."
>
    <x-page-header
        title="Ecoles"
        subtitle="Centralisez ici la consultation, l'activation, la mise a jour et la suppression des ecoles."
        eyebrow="Management"
    >
        <x-ui.button :href="route('super.schools.create')" variant="primary">
            Nouvelle ecole
        </x-ui.button>
    </x-page-header>

    <div class="super-kpi-grid xl:grid-cols-3">
        <x-super.stat label="Total" :value="$schoolsCount" meta="Ecoles repertoriees" tone="slate" />
        <x-super.stat label="Actives" :value="$activeSchoolsCount" meta="Acces autorise aux utilisateurs" tone="emerald" />
        <x-super.stat label="Inactives" :value="$inactiveSchoolsCount" meta="A verifier ou reactiver" tone="rose" />
    </div>

    <section class="super-toolbar">
        <div>
            <p class="text-sm font-semibold text-slate-950">Recherche et filtres</p>
            <p class="mt-1 text-sm text-slate-500">Affinez la liste par nom, slug, sous-domaine ou statut sans perdre l acces aux actions principales.</p>
        </div>

        <form method="GET" class="w-full lg:w-auto">
            <div class="super-filter-grid">
                <input
                    type="search"
                    name="q"
                    value="{{ $q ?? '' }}"
                    class="app-input"
                    placeholder="Rechercher une ecole, un slug ou un sous-domaine"
                >

                <select name="status" class="app-input">
                    <option value="">Tous les statuts</option>
                    <option value="active" @selected(($status ?? '') === 'active')>Actives</option>
                    <option value="inactive" @selected(($status ?? '') === 'inactive')>Inactives</option>
                </select>

                <x-ui.button type="submit" variant="primary">
                    Filtrer
                </x-ui.button>

                <x-ui.button :href="route('super.schools.index')" variant="secondary">
                    Reinitialiser
                </x-ui.button>
            </div>
        </form>
    </section>

    <x-super.panel
        title="Liste des ecoles"
        subtitle="Chaque ligne donne l essentiel : activite, volume et actions de gestion."
    >
        <x-slot:actions>
            <div class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-500">
                {{ $schools->total() }} resultat{{ $schools->total() > 1 ? 's' : '' }}
            </div>
        </x-slot:actions>

        @if($schools->isEmpty())
            <div class="super-empty">
                <x-ui.empty-state title="Aucune ecole trouvee" description="Aucune ecole ne correspond aux filtres actuels. Essayez un autre critere ou creez un nouvel espace.">
                    <div class="flex flex-wrap justify-center gap-3">
                        <x-ui.button :href="route('super.schools.index')" variant="secondary">
                            Retirer les filtres
                        </x-ui.button>
                        <x-ui.button :href="route('super.schools.create')" variant="primary">
                            Creer une ecole
                        </x-ui.button>
                    </div>
                </x-ui.empty-state>
            </div>
        @else
            <div class="super-table-wrap">
                <div class="overflow-x-auto">
                    <table class="super-table">
                        <thead>
                            <tr>
                                <th>Ecole</th>
                                <th>Utilisateurs</th>
                                <th>Eleves</th>
                                <th>Statut</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($schools as $school)
                                @php
                                    $logoPath = $school->logo_path;
                                    $logoUrl = $logoPath && Storage::disk('public')->exists(ltrim($logoPath, '/'))
                                        ? asset('storage/' . ltrim($logoPath, '/'))
                                        : asset('images/edulogo.jpg');
                                @endphp

                                <tr>
                                    <td>
                                        <div class="flex min-w-[16rem] items-start gap-4">
                                            <div class="h-12 w-12 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
                                                <img src="{{ $logoUrl }}" alt="Logo {{ $school->name }}" class="h-full w-full object-cover">
                                            </div>
                                            <div class="min-w-0">
                                                <p class="truncate font-semibold text-slate-950">{{ $school->name }}</p>
                                                <p class="mt-1 text-xs text-slate-500">{{ $school->slug }}</p>
                                                <p class="mt-1 text-xs text-sky-700">{{ $school->appUrl() }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <p class="font-semibold text-slate-950">{{ $school->users_count }}</p>
                                        <p class="mt-1 text-xs text-slate-500">comptes lies</p>
                                    </td>
                                    <td>
                                        <p class="font-semibold text-slate-950">{{ $school->students_count }}</p>
                                        <p class="mt-1 text-xs text-slate-500">inscriptions</p>
                                    </td>
                                    <td>
                                        <span class="super-status-badge {{ $school->is_active ? 'super-status-active' : 'super-status-inactive' }}">
                                            {{ $school->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="super-action-cluster justify-end">
                                            <x-ui.button :href="route('super.schools.edit', $school)" variant="secondary" size="sm">
                                                Modifier
                                            </x-ui.button>

                                            <form method="POST" action="{{ route('super.schools.toggle', $school) }}">
                                                @csrf
                                                <x-ui.button type="submit" :variant="$school->is_active ? 'ghost' : 'outline'" size="sm">
                                                    {{ $school->is_active ? 'Desactiver' : 'Activer' }}
                                                </x-ui.button>
                                            </form>

                                            <form
                                                method="POST"
                                                action="{{ route('super.schools.destroy', $school) }}"
                                                onsubmit="return confirm('Supprimer definitivement cette ecole, ses utilisateurs et ses donnees associees ?');"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <x-ui.button type="submit" variant="danger" size="sm">
                                                    Supprimer
                                                </x-ui.button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <div class="pt-2">
            {{ $schools->links() }}
        </div>
    </x-super.panel>
</x-super-layout>
