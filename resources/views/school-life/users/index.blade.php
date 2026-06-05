<x-school-life-layout title="Utilisateurs" subtitle="Gestion des comptes Élèves, parents, enseignants, chauffeurs et responsables scolaires.">
    @php
        $roleLabels = \App\Models\User::roleLabels();
        $roles = [
            '' => 'Tous',
            \App\Models\User::ROLE_DIRECTOR => 'Directeurs',
            \App\Models\User::ROLE_TEACHER => 'Enseignants',
            \App\Models\User::ROLE_PARENT => 'Parents',
            \App\Models\User::ROLE_STUDENT => 'Élèves',
            \App\Models\User::ROLE_SCHOOL_LIFE => 'Responsables scolaires',
            \App\Models\User::ROLE_ACCUEIL => 'Accueil',
            \App\Models\User::ROLE_CHAUFFEUR => 'Chauffeurs',
        ];
    @endphp

    <section class="overflow-hidden rounded-[32px] border border-sky-100 bg-[radial-gradient(circle_at_top_right,_rgba(14,165,233,0.18),_transparent_32%),radial-gradient(circle_at_bottom_left,_rgba(16,185,129,0.14),_transparent_34%),linear-gradient(135deg,#ffffff,#f8fbff_52%,#eefdf8)] px-6 py-6 text-slate-950 shadow-xl shadow-slate-200/70 md:px-8">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="max-w-2xl">
                <div class="inline-flex rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-sky-700">
                    Comptes
                </div>
                <h1 class="mt-4 text-3xl font-semibold tracking-tight md:text-4xl">Utilisateurs</h1>
                <p class="mt-3 text-sm leading-6 text-slate-600">
                    Creez et mettez a jour les comptes operationnels sans accès admin.
                </p>
            </div>

            <x-ui.button :href="route('school-life.users.create')" variant="primary">
                Nouvel utilisateur
            </x-ui.button>
        </div>
    </section>

    <section class="mt-6 rounded-[28px] border border-slate-200 bg-white px-5 py-5 shadow-sm">
        <form method="GET" action="{{ route('school-life.users.index') }}" class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_220px_auto] lg:items-end">
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-800">Recherche</label>
                <input name="q" value="{{ $q ?? '' }}" class="app-input" placeholder="Nom, email ou téléphone">
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-800">Role</label>
                <select name="role" class="app-input">
                    @foreach($roles as $key => $label)
                        <option value="{{ $key }}" @selected((string) ($role ?? '') === (string) $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <x-ui.button type="submit" variant="primary">Filtrer</x-ui.button>
                <x-ui.button :href="route('school-life.users.index')" variant="secondary">Reset</x-ui.button>
            </div>
        </form>
    </section>

    <section class="mt-6 overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-6 py-5">
            <h2 class="text-xl font-semibold tracking-tight text-slate-950">Liste des utilisateurs</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $users->total() }} resultat(s)</p>
        </div>

        <div class="overflow-x-auto">
            <table class="app-table">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Contact</th>
                        <th>Role</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td class="font-semibold text-slate-950">{{ $user->name }}</td>
                            <td>
                                <div class="text-sm text-slate-700">{{ $user->email }}</div>
                                <div class="text-xs text-slate-500">{{ $user->phone ?: 'Téléphone non renseigne' }}</div>
                            </td>
                            <td>
                                <x-ui.badge :variant="\App\Models\User::badgeVariantForRole($user->role)">
                                    {{ $roleLabels[$user->role] ?? \App\Models\User::labelForRole($user->role) }}
                                </x-ui.badge>
                            </td>
                            <td class="text-right">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <x-ui.button :href="route('school-life.users.show', $user)" variant="ghost" size="sm">Voir</x-ui.button>
                                    <x-ui.button :href="route('school-life.users.edit', $user)" variant="secondary" size="sm">Modifier</x-ui.button>
                                    <form method="POST" action="{{ route('school-life.users.destroy', $user) }}" onsubmit="return confirm('Supprimer cet utilisateur ?')">
                                        @csrf
                                        @method('DELETE')
                                        <x-ui.button type="submit" variant="danger" size="sm">Supprimer</x-ui.button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-10 text-center text-sm text-slate-500">Aucun utilisateur trouve.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-100 px-6 py-4">
            {{ $users->links() }}
        </div>
    </section>
</x-school-life-layout>
