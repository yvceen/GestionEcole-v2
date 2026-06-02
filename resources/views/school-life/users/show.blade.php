<x-school-life-layout :title="'Utilisateur - ' . $user->name" subtitle="Informations de contact, role et liens familiaux.">
    @php
        $roleLabel = \App\Models\User::labelForRole($user->role);
        $isParent = (string) $user->role === \App\Models\User::ROLE_PARENT;
        $isStudent = (string) $user->role === \App\Models\User::ROLE_STUDENT;
        $children = $linkedChildren ?? collect();
        $student = $linkedStudent;
    @endphp

    <section class="rounded-[32px] border border-slate-200 bg-white px-6 py-6 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="app-overline">Profil utilisateur</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">{{ $user->name }}</h1>
                <div class="mt-3 flex flex-wrap gap-2">
                    <x-ui.badge :variant="\App\Models\User::badgeVariantForRole($user->role)">{{ $roleLabel }}</x-ui.badge>
                    <x-ui.badge :variant="$user->is_active ? 'success' : 'danger'">{{ $user->is_active ? 'Actif' : 'Inactif' }}</x-ui.badge>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <x-ui.button :href="route('school-life.users.edit', $user)" variant="secondary">Modifier</x-ui.button>
                <x-ui.button :href="route('school-life.users.index')" variant="ghost">Retour</x-ui.button>
            </div>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-3">
            <article class="rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-4">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Email</p>
                <p class="mt-2 break-all text-sm font-semibold text-slate-950">{{ $user->email ?: '-' }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-4">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Téléphone</p>
                <p class="mt-2 text-sm font-semibold text-slate-950">{{ $user->phone ?: '-' }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-4">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Créé le</p>
                <p class="mt-2 text-sm font-semibold text-slate-950">{{ optional($user->created_at)->format('d/m/Y H:i') ?: '-' }}</p>
            </article>
        </div>
    </section>

    @if($isParent)
        <section class="mt-6 rounded-[28px] border border-slate-200 bg-white px-6 py-6 shadow-sm">
            <h2 class="text-xl font-semibold tracking-tight text-slate-950">Parent et enfants liés</h2>
            <p class="mt-1 text-sm text-slate-500">Coordonnées parent et Élèves rattachés.</p>

            @if($children->isNotEmpty())
                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    @foreach($children as $child)
                        <article class="rounded-2xl border border-slate-200 bg-emerald-50/40 px-5 py-5">
                            <p class="font-semibold text-slate-950">{{ $child->full_name }}</p>
                            <p class="mt-1 text-sm text-slate-600">Classe: {{ $child->classroom?->name ?: '-' }}</p>
                            <p class="mt-1 text-sm text-slate-600">Email Élève: {{ $child->studentUser?->email ?: '-' }}</p>
                        </article>
                    @endforeach
                </div>
            @else
                <p class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 px-5 py-5 text-sm text-slate-500">Aucun enfant lié.</p>
            @endif
        </section>
    @endif

    @if($isStudent && $student)
        <section class="mt-6 rounded-[28px] border border-slate-200 bg-white px-6 py-6 shadow-sm">
            <h2 class="text-xl font-semibold tracking-tight text-slate-950">Dossier Élève</h2>
            <div class="mt-5 grid gap-4 md:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-sky-50/50 px-5 py-5">
                    <p class="text-sm text-slate-600">Classe</p>
                    <p class="mt-1 font-semibold text-slate-950">{{ $student->classroom?->name ?: '-' }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-sky-50/50 px-5 py-5">
                    <p class="text-sm text-slate-600">Parent</p>
                    <p class="mt-1 font-semibold text-slate-950">{{ $student->parentUser?->name ?: '-' }}</p>
                    <p class="mt-1 text-sm text-slate-600">{{ $student->parentUser?->phone ?: $student->parentUser?->email }}</p>
                </div>
            </div>
        </section>
    @endif
</x-school-life-layout>
