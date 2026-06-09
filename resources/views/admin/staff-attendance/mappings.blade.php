<x-admin-layout title="Correspondances ZKBioTime" subtitle="Reliez les codes employés de la pointeuse aux comptes My Edu du personnel.">
    <x-ui.page-header
        title="Correspondances ZKBioTime"
        subtitle="Quand un code est relié à un utilisateur, les anciens et nouveaux pointages deviennent lisibles par nom et rôle."
    >
        <x-slot name="actions">
            <x-ui.button :href="route('admin.staff-attendance.index')" variant="secondary">
                Voir les pointages
            </x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <section class="rounded-[28px] border border-slate-200 bg-white px-5 py-5 shadow-sm">
        <form method="GET" action="{{ route('admin.staff-attendance.mappings') }}" class="grid gap-3 md:grid-cols-[minmax(0,1fr)_220px_auto] md:items-end">
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-800">Recherche</label>
                <input name="q" value="{{ $q }}" class="app-input" placeholder="Code, nom, département...">
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-800">Statut</label>
                <select name="status" class="app-input">
                    <option value="">Tous</option>
                    <option value="mapped" @selected($status === 'mapped')>Reliés</option>
                    <option value="unmapped" @selected($status === 'unmapped')>Non reliés</option>
                </select>
            </div>
            <div class="flex gap-2">
                <x-ui.button type="submit" variant="primary">Filtrer</x-ui.button>
                <x-ui.button :href="route('admin.staff-attendance.mappings')" variant="secondary">Reset</x-ui.button>
            </div>
        </form>
    </section>

    <section class="mt-6 grid gap-4">
        @forelse($mappings as $mapping)
            <article class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm">
                <form method="POST" action="{{ route('admin.staff-attendance.mappings.update', $mapping) }}" class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_360px_140px_auto] lg:items-center">
                    @csrf
                    @method('PUT')

                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <x-ui.badge :variant="$mapping->user_id ? 'success' : 'warning'">Code {{ $mapping->employee_code }}</x-ui.badge>
                            <x-ui.badge variant="info">{{ $mapping->department_name ?: 'Département non défini' }}</x-ui.badge>
                        </div>
                        <h2 class="mt-3 text-lg font-semibold text-slate-950">{{ $mapping->employee_name ?: 'Employé ZKBioTime' }}</h2>
                        <p class="mt-1 text-sm text-slate-500">Dernier pointage : {{ optional($mapping->last_seen_at)->format('d/m/Y H:i') ?: 'Pas encore importé' }}</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-800">Compte My Edu</label>
                        <select name="user_id" class="app-input">
                            <option value="">Non relié</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" @selected((int) $mapping->user_id === (int) $user->id)>
                                    {{ $user->name }} - {{ \App\Models\User::labelForRole($user->role) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <label class="flex items-center gap-2 rounded-2xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700">
                        <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300" @checked($mapping->is_active)>
                        Actif
                    </label>

                    <x-ui.button type="submit" variant="primary">Enregistrer</x-ui.button>
                </form>
            </article>
        @empty
            <div class="rounded-[28px] border border-slate-200 bg-white px-6 py-12 text-center text-sm text-slate-500">
                Aucune correspondance pour le moment. Lancez un import ZKBioTime pour créer les premiers codes employés.
            </div>
        @endforelse
    </section>

    <div class="mt-6">
        {{ $mappings->links() }}
    </div>
</x-admin-layout>
