<x-admin-layout :title="'Profil utilisateur - ' . $user->name">
    @php
        $roleLabel = \App\Models\User::labelForRole($user->role);
        $roleVariant = \App\Models\User::badgeVariantForRole($user->role);
        $statusLabel = $user->is_active ? 'Actif' : 'Inactif';
        $statusVariant = $user->is_active ? 'success' : 'danger';
        $schoolName = $user->school?->name ?? '-';
        $student = $linkedStudent;
        $children = $linkedChildren ?? collect();
        $subjects = $teacherSubjects ?? collect();
        $classrooms = $teacherClassrooms ?? collect();
        $driverVehicles = $driverVehicles ?? collect();
        $isStudent = (string) $user->role === \App\Models\User::ROLE_STUDENT;
        $isParent = (string) $user->role === \App\Models\User::ROLE_PARENT;
        $isTeacher = (string) $user->role === \App\Models\User::ROLE_TEACHER;
        $isDriver = (string) $user->role === \App\Models\User::ROLE_CHAUFFEUR;
    @endphp

    <div class="mx-auto w-full max-w-7xl space-y-5">
        <section class="app-card px-5 py-5 md:px-6">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0 max-w-3xl">
                    <p class="app-overline">Utilisateurs</p>
                    <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">{{ $user->name }}</h1>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Profil detaille du compte, des relations scolaires et des affectations liees a cet utilisateur.
                    </p>
                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <x-ui.badge :variant="$roleVariant">{{ $roleLabel }}</x-ui.badge>
                        <x-ui.badge :variant="$statusVariant">{{ $statusLabel }}</x-ui.badge>
                    </div>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <x-ui.button :href="route('admin.users.edit', $user)" variant="secondary">
                        Modifier
                    </x-ui.button>
                    <x-ui.button :href="route('admin.users.index')" variant="ghost">
                        Retour a la liste
                    </x-ui.button>
                </div>
            </div>

            <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <article class="app-stat-card min-h-[9rem]">
                    <p class="app-stat-label">Compte</p>
                    <p class="app-stat-value text-lg">{{ $user->email ?: '-' }}</p>
                    <p class="app-stat-meta">Adresse de connexion.</p>
                </article>
                <article class="app-stat-card min-h-[9rem]">
                    <p class="app-stat-label">Téléphone</p>
                    <p class="app-stat-value text-lg">{{ $user->phone ?: '-' }}</p>
                    <p class="app-stat-meta">Numéro principal disponible.</p>
                </article>
                <article class="app-stat-card min-h-[9rem]">
                    <p class="app-stat-label">École</p>
                    <p class="app-stat-value text-lg">{{ $schoolName }}</p>
                    <p class="app-stat-meta">Contexte scolaire actuel.</p>
                </article>
                <article class="app-stat-card min-h-[9rem]">
                    <p class="app-stat-label">Créé le</p>
                    <p class="app-stat-value text-lg">{{ optional($user->created_at)->format('d/m/Y') ?: '-' }}</p>
                    <p class="app-stat-meta">{{ optional($user->created_at)->format('H:i') ?: 'Heure indisponible' }}</p>
                </article>
            </div>
        </section>

        <div class="grid gap-5 xl:grid-cols-[minmax(0,1.4fr)_minmax(320px,0.9fr)]">
            <section class="app-card px-5 py-5 md:px-6">
                <h2 class="text-lg font-semibold text-slate-950">Compte</h2>
                <dl class="mt-5 grid gap-4 md:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-4">
                        <dt class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Nom complet</dt>
                        <dd class="mt-2 text-sm font-semibold text-slate-900">{{ $user->name }}</dd>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-4">
                        <dt class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Role</dt>
                        <dd class="mt-2 text-sm font-semibold text-slate-900">{{ $roleLabel }}</dd>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-4">
                        <dt class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Email</dt>
                        <dd class="mt-2 text-sm font-semibold text-slate-900 break-all">{{ $user->email ?: '-' }}</dd>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-4">
                        <dt class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Statut</dt>
                        <dd class="mt-2 text-sm font-semibold text-slate-900">{{ $statusLabel }}</dd>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-4">
                        <dt class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Téléphone</dt>
                        <dd class="mt-2 text-sm font-semibold text-slate-900">{{ $user->phone ?: '-' }}</dd>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-4">
                        <dt class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">École</dt>
                        <dd class="mt-2 text-sm font-semibold text-slate-900">{{ $schoolName }}</dd>
                    </div>
                </dl>
            </section>

            <section class="app-card px-5 py-5 md:px-6">
                <h2 class="text-lg font-semibold text-slate-950">Metadonnees</h2>
                <div class="mt-5 space-y-4">
                    <div class="rounded-2xl border border-slate-200 px-4 py-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">ID utilisateur</p>
                        <p class="mt-2 text-sm font-semibold text-slate-900">#{{ $user->id }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 px-4 py-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Compte créé le</p>
                        <p class="mt-2 text-sm font-semibold text-slate-900">{{ optional($user->created_at)->format('d/m/Y H:i') ?: '-' }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 px-4 py-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Derniere mise a jour</p>
                        <p class="mt-2 text-sm font-semibold text-slate-900">{{ optional($user->updated_at)->format('d/m/Y H:i') ?: '-' }}</p>
                    </div>
                    @if($user->parentProfile)
                        <div class="rounded-2xl border border-slate-200 px-4 py-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Profil parent</p>
                            <p class="mt-2 text-sm text-slate-700">
                                Mode: <span class="font-semibold text-slate-900">{{ $user->parentProfile->billing_type ?: '-' }}</span>
                            </p>
                            <p class="mt-1 text-sm text-slate-700">
                                Début: <span class="font-semibold text-slate-900">{{ $user->parentProfile->starts_month ?: '-' }}</span>
                            </p>
                        </div>
                    @endif
                </div>
            </section>
        </div>

        @if($isStudent)
            <section class="app-card px-5 py-5 md:px-6">
                <div class="flex flex-col gap-2">
                    <h2 class="text-lg font-semibold text-slate-950">Dossier Élève</h2>
                    <p class="text-sm text-slate-500">Informations reliées au dossier scolaire réel de cet Élève.</p>
                </div>

                @if($student)
                    <div class="mt-5 grid gap-4 lg:grid-cols-2">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50/70 px-5 py-5">
                            <h3 class="text-sm font-semibold text-slate-900">Infos scolaires</h3>
                            <dl class="mt-4 space-y-3 text-sm">
                                <div class="flex items-start justify-between gap-4">
                                    <dt class="text-slate-500">Classe</dt>
                                    <dd class="text-right font-semibold text-slate-900">{{ $student->classroom?->name ?: '-' }}</dd>
                                </div>
                                <div class="flex items-start justify-between gap-4">
                                    <dt class="text-slate-500">Date de naissance</dt>
                                    <dd class="text-right font-semibold text-slate-900">{{ optional($student->birth_date)->format('d/m/Y') ?: '-' }}</dd>
                                </div>
                                <div class="flex items-start justify-between gap-4">
                                    <dt class="text-slate-500">Genre</dt>
                                    <dd class="text-right font-semibold text-slate-900">{{ $student->gender ?: '-' }}</dd>
                                </div>
                                <div class="flex items-start justify-between gap-4">
                                    <dt class="text-slate-500">Code / matricule</dt>
                                    <dd class="text-right font-semibold text-slate-900">{{ $student->card_token ?: '-' }}</dd>
                                </div>
                                <div class="flex items-start justify-between gap-4">
                                    <dt class="text-slate-500">Dossier Élève</dt>
                                    <dd class="text-right font-semibold text-slate-900">#{{ $student->id }}</dd>
                                </div>
                            </dl>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-white px-5 py-5">
                            <h3 class="text-sm font-semibold text-slate-900">Lien parent</h3>
                            @if($student->parentUser)
                                <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-4">
                                    <p class="text-base font-semibold text-slate-950">{{ $student->parentUser->name }}</p>
                                    <p class="mt-1 text-sm text-slate-600 break-all">{{ $student->parentUser->email ?: '-' }}</p>
                                    <p class="mt-1 text-sm text-slate-600">{{ $student->parentUser->phone ?: '-' }}</p>
                                </div>
                            @else
                                <p class="mt-4 text-sm text-slate-500">Aucun parent lié a ce dossier Élève.</p>
                            @endif

                            <div class="mt-4 rounded-2xl border border-slate-200 bg-white px-4 py-4">
                                <p class="text-sm font-semibold text-slate-900">Transport</p>
                                @if($student->transportAssignment)
                                    <p class="mt-2 text-sm text-slate-700">
                                        Route: <span class="font-semibold text-slate-900">{{ $student->transportAssignment->route?->route_name ?: '-' }}</span>
                                    </p>
                                    <p class="mt-1 text-sm text-slate-700">
                                        Vehicule: <span class="font-semibold text-slate-900">{{ $student->transportAssignment->vehicle?->name ?: ($student->transportAssignment->vehicle?->registration_number ?: '-') }}</span>
                                    </p>
                                @else
                                    <p class="mt-2 text-sm text-slate-500">Aucune affectation transport active.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @else
                    <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 px-5 py-5 text-sm text-slate-500">
                        Aucun dossier Élève lié a ce compte.
                    </div>
                @endif
            </section>
        @endif

        @if($isParent)
            <section class="app-card px-5 py-5 md:px-6">
                <div class="flex flex-col gap-2">
                    <h2 class="text-lg font-semibold text-slate-950">Liens familiaux</h2>
                    <p class="text-sm text-slate-500">Enfants reliés a ce compte parent dans l'École courante.</p>
                </div>

                @if($children->isNotEmpty())
                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        @foreach($children as $child)
                            <article class="rounded-2xl border border-slate-200 bg-slate-50/70 px-5 py-5">
                                <p class="text-base font-semibold text-slate-950">{{ $child->full_name }}</p>
                                <p class="mt-1 text-sm text-slate-600">Classe: {{ $child->classroom?->name ?: '-' }}</p>
                                <p class="mt-1 text-sm text-slate-600">Code / matricule: {{ $child->card_token ?: '-' }}</p>
                                <p class="mt-1 text-sm text-slate-600 break-all">Email Élève: {{ $child->studentUser?->email ?: '-' }}</p>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 px-5 py-5 text-sm text-slate-500">
                        Aucun enfant lié a ce parent dans cette École.
                    </div>
                @endif
            </section>
        @endif

        @if($isTeacher)
            <div class="grid gap-5 xl:grid-cols-2">
                <section class="app-card px-5 py-5 md:px-6">
                    <div class="flex flex-col gap-2">
                        <h2 class="text-lg font-semibold text-slate-950">Affectation enseignants</h2>
                        <p class="text-sm text-slate-500">Matières reliées au pivot enseignant-matières existant.</p>
                    </div>

                    @if($subjects->isNotEmpty())
                        <div class="mt-5 grid gap-3">
                            @foreach($subjects as $subject)
                                <div class="rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-4">
                                    <p class="font-semibold text-slate-900">{{ $subject->name }}</p>
                                    <p class="mt-1 text-sm text-slate-600">{{ $subject->code ?: 'Aucun code' }}</p>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 px-5 py-5 text-sm text-slate-500">
                            Aucune matiere affectee.
                        </div>
                    @endif
                </section>

                <section class="app-card px-5 py-5 md:px-6">
                    <div class="flex flex-col gap-2">
                        <h2 class="text-lg font-semibold text-slate-950">Classes</h2>
                        <p class="text-sm text-slate-500">Classes reliées a l affectation enseignant-classe existante.</p>
                    </div>

                    @if($classrooms->isNotEmpty())
                        <div class="mt-5 grid gap-3">
                            @foreach($classrooms as $classroom)
                                <div class="rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-4">
                                    <p class="font-semibold text-slate-900">{{ $classroom->name }}</p>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 px-5 py-5 text-sm text-slate-500">
                            Aucune classe affectee.
                        </div>
                    @endif
                </section>
            </div>
        @endif

        @if($isDriver)
            <section class="app-card px-5 py-5 md:px-6">
                <div class="flex flex-col gap-2">
                    <h2 class="text-lg font-semibold text-slate-950">Transport</h2>
                    <p class="text-sm text-slate-500">Véhicules et routes liés a ce chauffeur dans l'École courante.</p>
                </div>

                @if($driverVehicles->isNotEmpty())
                    <div class="mt-5 grid gap-4 lg:grid-cols-2">
                        @foreach($driverVehicles as $vehicle)
                            <article class="rounded-2xl border border-slate-200 bg-slate-50/70 px-5 py-5">
                                <p class="text-base font-semibold text-slate-950">{{ $vehicle->name ?: 'Vehicule' }}</p>
                                <p class="mt-1 text-sm text-slate-600">Immatriculation: {{ $vehicle->registration_number ?: ($vehicle->plate_number ?: '-') }}</p>
                                <p class="mt-1 text-sm text-slate-600">Type: {{ $vehicle->vehicle_type ?: '-' }}</p>
                                <p class="mt-1 text-sm text-slate-600">Capacite: {{ $vehicle->capacity ?: '-' }}</p>
                                <div class="mt-4">
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Routes</p>
                                    @if($vehicle->routes->isNotEmpty())
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            @foreach($vehicle->routes as $route)
                                                <span class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-800">
                                                    {{ $route->route_name }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="mt-2 text-sm text-slate-500">Aucune route reliée.</p>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 px-5 py-5 text-sm text-slate-500">
                        Aucun vehicule ou circuit relié a ce chauffeur.
                    </div>
                @endif
            </section>
        @endif

        @if(! $isStudent && ! $isParent && ! $isTeacher && ! $isDriver)
            <section class="app-card px-5 py-5 md:px-6">
                <div class="flex flex-col gap-2">
                    <h2 class="text-lg font-semibold text-slate-950">Informations complementaires</h2>
                    <p class="text-sm text-slate-500">Vue generique pour les roles administratifs ou support.</p>
                </div>

                <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50/70 px-5 py-5">
                    <p class="text-sm text-slate-700">
                        Ce compte ne dispose pas de section spécialisée supplémentaire dans les données actuelles.
                    </p>
                    <p class="mt-2 text-sm text-slate-700">
                        Role courant: <span class="font-semibold text-slate-900">{{ $roleLabel }}</span>
                    </p>
                    <p class="mt-1 text-sm text-slate-700">
                        École: <span class="font-semibold text-slate-900">{{ $schoolName }}</span>
                    </p>
                </div>
            </section>
        @endif
    </div>
</x-admin-layout>
