<x-admin-layout title="Creer un utilisateur">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Creer un utilisateur</h1>
            <p class="mt-1 text-sm text-slate-500">Ajouter un compte admin, directeur, enseignant, parent, eleve, responsable scolaire ou chauffeur.</p>
        </div>
        <a href="{{ route('admin.users.index') }}" class="text-sm font-semibold text-slate-700 hover:underline">Retour</a>
    </div>

    @if($errors->any())
        <div class="mt-6 rounded-3xl border border-red-200 bg-red-50 p-4 text-red-900">
            <ul class="ml-5 list-disc">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.users.store') }}" class="mt-8 space-y-5 rounded-[28px] border border-black/10 bg-white/80 p-6 shadow-sm">
        @csrf

        <div>
            <label class="mb-1 block text-sm font-semibold">Nom complet</label>
            <input
                name="name"
                value="{{ old('name') }}"
                class="w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5"
                placeholder="Ex : Ahmed El Amrani"
                required
            />
        </div>

        <div>
            <label class="mb-1 block text-sm font-semibold">E-mail</label>
            <input
                name="email"
                type="email"
                value="{{ old('email') }}"
                class="w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5"
                placeholder="exemple@mail.com"
                required
            />
        </div>

        <div>
            <label class="mb-1 block text-sm font-semibold">Telephone (optionnel)</label>
            <input
                name="phone"
                value="{{ old('phone') }}"
                class="w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5"
                placeholder="+212..."
            />
        </div>

        <div>
            <label class="mb-1 block text-sm font-semibold">Role</label>
            <select id="role" name="role" class="w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5" required>
                @foreach($roles as $role)
                    <option value="{{ $role }}" @selected(old('role', \App\Models\User::ROLE_PARENT) === $role)>
                        {{ $roleLabels[$role] ?? \App\Models\User::labelForRole($role) }}
                    </option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-slate-500">Le directeur a uniquement acces aux modules pedagogiques.</p>
        </div>

        <div id="student-fields" class="{{ old('role') === \App\Models\User::ROLE_STUDENT ? '' : 'hidden' }} space-y-5 rounded-2xl border border-sky-100 bg-sky-50/70 p-4">
            <div>
                <p class="text-sm font-semibold text-slate-900">Dossier eleve</p>
                <p class="mt-1 text-xs text-slate-600">Un compte eleve cree ici doit aussi generer son dossier eleve lie.</p>
            </div>

            <div>
                <label class="mb-1 block text-sm font-semibold">Classe</label>
                <select name="classroom_id" class="w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5">
                    <option value="">Choisir une classe</option>
                    @foreach($classrooms as $classroom)
                        <option value="{{ $classroom->id }}" @selected((string) old('classroom_id') === (string) $classroom->id)>
                            {{ $classroom->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-semibold">Parent lie (optionnel)</label>
                <select name="parent_user_id" class="w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5">
                    <option value="">Aucun parent lie</option>
                    @foreach($parents as $parent)
                        <option value="{{ $parent->id }}" @selected((string) old('parent_user_id') === (string) $parent->id)>
                            {{ $parent->name }} ({{ $parent->email }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-semibold">Date de naissance (optionnel)</label>
                    <input
                        name="birth_date"
                        type="date"
                        value="{{ old('birth_date') }}"
                        class="w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5"
                    />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold">Genre (optionnel)</label>
                    <select name="gender" class="w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5">
                        <option value="">Non renseigne</option>
                        <option value="male" @selected(old('gender') === 'male')>Garcon</option>
                        <option value="female" @selected(old('gender') === 'female')>Fille</option>
                    </select>
                </div>
            </div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-semibold">Mot de passe</label>
            <input
                id="user_password"
                name="password"
                type="text"
                class="w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5"
                placeholder="Minimum 8 caracteres avec lettres et chiffres"
                required
            />
            <x-ui.password-tools target="user_password" helper="Copiez ce mot de passe pour le communiquer a l'utilisateur." />
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('admin.users.index') }}" class="rounded-2xl border border-black/10 bg-white px-5 py-2.5 text-sm font-semibold hover:bg-slate-50">
                Annuler
            </a>
            <button class="rounded-2xl bg-black px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-900">
                Creer
            </button>
        </div>
    </form>

    <script>
        (function () {
            const roleField = document.getElementById('role');
            const studentFields = document.getElementById('student-fields');
            if (!roleField || !studentFields) return;

            const syncVisibility = () => {
                studentFields.classList.toggle('hidden', roleField.value !== 'student');
            };

            roleField.addEventListener('change', syncVisibility);
            syncVisibility();
        })();
    </script>
</x-admin-layout>
