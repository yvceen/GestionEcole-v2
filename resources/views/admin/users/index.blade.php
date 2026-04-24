<x-admin-layout title="Utilisateurs">
    <x-page-header
        title="Utilisateurs"
        subtitle="Recherchez, filtrez par rôle et gérez les comptes depuis une interface unifiée."
        eyebrow="Administration"
    >
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-3">
            <form method="GET" action="{{ route('admin.users.index') }}" class="relative w-full sm:w-80">
                @if(!empty($role))
                    <input type="hidden" name="role" value="{{ $role }}">
                @endif

                <input
                    id="uq"
                    name="q"
                    value="{{ $q ?? '' }}"
                    autocomplete="off"
                    placeholder="Rechercher par nom, email ou téléphone..."
                    class="app-input pr-10"
                />

                <button
                    type="submit"
                    class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg border border-slate-200 bg-white px-2 py-1 text-xs font-semibold text-slate-500 hover:bg-slate-50"
                >
                    OK
                </button>

                <div id="userSuggestBox" class="absolute z-50 mt-2 hidden w-full overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-lg"></div>
            </form>

            <x-ui.button :href="route('admin.users.create')" variant="primary">
                Nouvel utilisateur
            </x-ui.button>
        </div>
    </x-page-header>

    @php
        $roles = [
            '' => 'Tous',
            'admin' => 'Admins',
            'super_admin' => 'Super admins',
            'director' => 'Directeurs',
            'teacher' => 'Enseignants',
            'parent' => 'Parents',
            'student' => 'Eleves',
            'school_life' => 'Responsables scolaires',
            'chauffeur' => 'Chauffeurs',
        ];
    @endphp

    <section class="app-card px-5 py-5">
        <div class="flex flex-wrap gap-2">
            @foreach($roles as $key => $label)
                <x-ui.button
                    :href="route('admin.users.index', array_filter(['role' => $key ?: null, 'q' => $q]))"
                    :variant="(string) ($role ?? '') === (string) $key ? 'outline' : 'ghost'"
                    size="sm"
                >
                    {{ $label }}
                </x-ui.button>
            @endforeach
        </div>
    </section>

    <x-ui.card title="Liste des utilisateurs" subtitle="Résultats : {{ $users->total() }}">
        <div class="overflow-hidden rounded-2xl border border-slate-200">
            <div class="overflow-x-auto">
                <table class="app-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nom</th>
                            <th>E-mail</th>
                            <th>Rôle</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td>{{ ($users->currentPage() - 1) * $users->perPage() + $loop->iteration }}</td>
                                <td class="font-medium text-slate-900">{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    <x-ui.badge :variant="\App\Models\User::badgeVariantForRole($user->role)">
                                        {{ \App\Models\User::labelForRole($user->role) }}
                                    </x-ui.badge>
                                </td>
                                <td class="text-right">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <x-ui.button :href="route('admin.users.show', $user)" variant="ghost" size="sm">
                                            Voir
                                        </x-ui.button>

                                        <x-ui.button :href="route('admin.users.edit', $user)" variant="secondary" size="sm">
                                            Modifier
                                        </x-ui.button>

                                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Supprimer cet utilisateur ?')">
                                            @csrf
                                            @method('DELETE')
                                            <x-ui.button type="submit" variant="danger" size="sm">
                                                Supprimer
                                            </x-ui.button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-10 text-center text-sm text-slate-500">Aucun résultat trouvé.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $users->links() }}
        </div>
    </x-ui.card>

    <script>
        (function () {
            const input = document.getElementById('uq');
            const box = document.getElementById('userSuggestBox');
            let timer = null;

            function hide() {
                box.classList.add('hidden');
                box.innerHTML = '';
            }

            function show() {
                box.classList.remove('hidden');
            }

            input.addEventListener('input', function () {
                const value = input.value.trim();
                clearTimeout(timer);

                if (value.length < 2) {
                    hide();
                    return;
                }

                timer = setTimeout(async () => {
                    try {
                        const url = new URL("{{ route('admin.users.suggest') }}", window.location.origin);
                        url.searchParams.set('q', value);
                        @if(!empty($role))
                            url.searchParams.set('role', "{{ $role }}");
                        @endif

                        const response = await fetch(url.toString(), { headers: { Accept: 'application/json' } });
                        const items = await response.json();

                        if (!items.length) {
                            hide();
                            return;
                        }

                        box.innerHTML = items.map((item) => `
                            <button type="button" class="w-full px-4 py-3 text-left hover:bg-slate-50">
                                <div class="text-sm font-semibold text-slate-900">${item.label}</div>
                                <div class="text-xs text-slate-500">${item.meta}</div>
                            </button>
                        `).join('');

                        Array.from(box.querySelectorAll('button')).forEach((button, index) => {
                            button.addEventListener('click', () => {
                                input.value = items[index].label;
                                hide();
                                input.closest('form').submit();
                            });
                        });

                        show();
                    } catch (error) {
                        hide();
                    }
                }, 180);
            });

            document.addEventListener('click', function (event) {
                if (!box.contains(event.target) && event.target !== input) {
                    hide();
                }
            });
        })();
    </script>
</x-admin-layout>
