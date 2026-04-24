<x-super-layout
    title="Tableau de bord"
    subtitle="Vue d'ensemble de la plateforme, des ecoles actives et des indicateurs d'utilisation."
>
    @php
        $recentSchools = $schools->take(5);
        $maxChartValue = max(max($chartValues ?: [0]), 1);
        $inactiveRate = $schoolsCount > 0 ? round(($inactiveSchoolsCount / $schoolsCount) * 100) : 0;
    @endphp

    <x-page-header
        title="Super Admin"
        subtitle="Supervisez les ecoles, reperez rapidement les points d'attention et lancez vos actions de gestion depuis un seul espace."
        eyebrow="Control center"
    >
        <x-ui.button :href="route('super.schools.index')" variant="secondary">
            Gerer les ecoles
        </x-ui.button>
        <x-ui.button :href="route('super.schools.create')" variant="primary">
            Ajouter une ecole
        </x-ui.button>
    </x-page-header>

    <div class="super-kpi-grid">
        <x-super.stat label="Ecoles" :value="$schoolsCount" meta="Espaces crees sur la plateforme" tone="slate">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10.5L12 5l9 5.5M5 9.8V20h14V9.8M9 20v-5h6v5M8 12h.01M12 12h.01M16 12h.01"/>
            </svg>
        </x-super.stat>

        <x-super.stat label="Ecoles actives" :value="$activeSchoolsCount" meta="Disponibles pour les utilisateurs" tone="emerald">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
        </x-super.stat>

        <x-super.stat label="Utilisateurs" :value="$usersCount" meta="Tous roles confondus" tone="sky">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2M16 7a4 4 0 1 1 0 8M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/>
            </svg>
        </x-super.stat>

        <x-super.stat label="Eleves" :value="$studentsCount" meta="Volume global des inscriptions" tone="amber">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 0v6m-6-3l6 3 6-3"/>
            </svg>
        </x-super.stat>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="super-mini-stat">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Nouvelles ecoles</p>
            <p class="mt-2 text-2xl font-semibold text-slate-950">{{ $schoolsCreatedThisMonth }}</p>
            <p class="mt-1 text-sm text-slate-500">Creees depuis le debut du mois.</p>
        </div>

        <div class="super-mini-stat">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Paiements ce mois</p>
            <p class="mt-2 text-2xl font-semibold text-slate-950">{{ $paymentsCountThisMonth }}</p>
            <p class="mt-1 text-sm text-slate-500">Operations enregistrees sur la plateforme.</p>
        </div>

        <div class="super-mini-stat">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Moyenne eleves/ecole</p>
            <p class="mt-2 text-2xl font-semibold text-slate-950">{{ number_format($avgStudentsPerSchool, 1) }}</p>
            <p class="mt-1 text-sm text-slate-500">Repere simple d'utilisation SaaS.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1.6fr)_minmax(18rem,0.9fr)]">
        <x-super.panel
            title="Activite de la plateforme"
            subtitle="Une lecture rapide des performances et de la repartition des comptes."
        >
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-[minmax(0,1.1fr)_minmax(16rem,0.9fr)]">
                <div class="rounded-[24px] border border-slate-200/80 bg-slate-50/70 p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="super-eyebrow">Encaissements</p>
                            <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">{{ number_format($revenueThisMonth, 0, ',', ' ') }} DH</p>
                            <p class="mt-1 text-sm text-slate-500">Montant collecte sur le mois en cours.</p>
                        </div>

                        <a href="{{ route('super.schools.index') }}" class="app-button-secondary min-h-10 rounded-full px-4 py-2 text-xs">
                            Voir les ecoles
                        </a>
                    </div>

                    <div class="mt-6 grid grid-cols-12 items-end gap-2">
                        @foreach($chartValues as $index => $value)
                            @php
                                $height = max(14, (int) round(($value / $maxChartValue) * 112));
                            @endphp
                            <div class="flex flex-col items-center gap-2">
                                <div
                                    class="w-full rounded-t-2xl bg-sky-600/85"
                                    style="height: {{ $height }}px"
                                    title="{{ $chartLabels[$index] }}: {{ number_format($value, 0, ',', ' ') }} DH"
                                ></div>
                                <span class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">
                                    {{ \Illuminate\Support\Str::limit($chartLabels[$index], 3, '') }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="super-mini-stat">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Administrateurs</p>
                        <p class="mt-2 text-2xl font-semibold text-slate-950">{{ $adminsCount }}</p>
                        <p class="mt-1 text-sm text-slate-500">Comptes responsables des operations locales.</p>
                    </div>

                    <div class="super-mini-stat">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Enseignants</p>
                        <p class="mt-2 text-2xl font-semibold text-slate-950">{{ $teachersCount }}</p>
                        <p class="mt-1 text-sm text-slate-500">Ressources pedagogiques actives dans les ecoles.</p>
                    </div>

                    <div class="super-mini-stat">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Parents</p>
                        <p class="mt-2 text-2xl font-semibold text-slate-950">{{ $parentsCount }}</p>
                        <p class="mt-1 text-sm text-slate-500">Comptes relies au suivi quotidien des eleves.</p>
                    </div>
                </div>
            </div>
        </x-super.panel>

        <x-super.panel
            title="Points d attention"
            subtitle="Les informations utiles pour savoir ou agir en priorite."
        >
            <div class="space-y-4">
                <div class="rounded-[24px] border border-slate-200/80 bg-slate-50/75 p-5">
                    <p class="text-sm font-semibold text-slate-900">Ecoles inactives</p>
                    <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">{{ $inactiveSchoolsCount }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ $inactiveRate }}% du parc n'est pas accessible actuellement.</p>
                    <div class="mt-4">
                        <x-ui.button :href="route('super.schools.index', ['status' => 'inactive'])" variant="secondary" size="sm">
                            Voir les ecoles inactives
                        </x-ui.button>
                    </div>
                </div>

                <div class="rounded-[24px] border border-slate-200/80 bg-slate-50/75 p-5">
                    <p class="text-sm font-semibold text-slate-900">Raccourcis de gestion</p>
                    <div class="mt-4 space-y-2">
                        <a href="{{ route('super.schools.create') }}" class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:text-slate-950">
                            <span>Onboarder une nouvelle ecole</span>
                            <span aria-hidden="true">+</span>
                        </a>
                        <a href="{{ route('super.schools.index') }}" class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:text-slate-950">
                            <span>Revoir toutes les ecoles</span>
                            <span aria-hidden="true">></span>
                        </a>
                    </div>
                </div>
            </div>
        </x-super.panel>
    </div>

    <x-super.panel
        title="Ecoles recentes"
        subtitle="Les derniers espaces crees, avec les actions les plus frequentes directement accessibles."
    >
        <x-slot:actions>
            <x-ui.button :href="route('super.schools.index')" variant="secondary" size="sm">
                Toutes les ecoles
            </x-ui.button>
        </x-slot:actions>

        @if($recentSchools->isEmpty())
            <div class="super-empty">
                <x-ui.empty-state title="Aucune ecole" description="Commencez par creer votre premiere ecole pour initialiser la plateforme.">
                    <x-ui.button :href="route('super.schools.create')" variant="primary">
                        Creer une ecole
                    </x-ui.button>
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
                            @foreach($recentSchools as $school)
                                <tr>
                                    <td>
                                        <div class="min-w-[14rem]">
                                            <p class="font-semibold text-slate-950">{{ $school->name }}</p>
                                            <p class="mt-1 text-xs text-slate-500">{{ $school->slug }}</p>
                                            <p class="mt-1 text-xs text-sky-700">{{ $school->appUrl() }}</p>
                                        </div>
                                    </td>
                                    <td>{{ $school->users_count }}</td>
                                    <td>{{ $school->students_count }}</td>
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
                                                <x-ui.button type="submit" variant="ghost" size="sm">
                                                    {{ $school->is_active ? 'Desactiver' : 'Activer' }}
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
    </x-super.panel>
</x-super-layout>
