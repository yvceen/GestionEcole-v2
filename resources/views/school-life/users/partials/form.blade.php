@php
    $user = $user ?? null;
    $linkedStudent = $linkedStudent ?? null;
    $selectedRole = old('role', $user?->role ?? \App\Models\User::ROLE_PARENT);
@endphp

<section class="mx-auto max-w-4xl rounded-[28px] border border-slate-200 bg-white px-6 py-6 shadow-sm">
    @if($errors->any())
        <x-ui.alert variant="error" class="mb-5">
            <ul class="list-disc pl-4">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-ui.alert>
    @endif

    <form method="POST" action="{{ $action }}" class="space-y-5">
        @csrf
        @if($method !== 'POST')
            @method($method)
        @endif

        <div class="grid gap-5 md:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-semibold">Nom complet</label>
                <input name="name" value="{{ old('name', $user?->name) }}" class="app-input" required>
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold">E-mail</label>
                <input name="email" type="email" value="{{ old('email', $user?->email) }}" class="app-input" required>
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold">Téléphone</label>
                <input name="phone" value="{{ old('phone', $user?->phone) }}" class="app-input" placeholder="06...">
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold">Role</label>
                <select id="role" name="role" class="app-input" required>
                    @foreach($roles as $role)
                        <option value="{{ $role }}" @selected($selectedRole === $role)>
                            {{ $roleLabels[$role] ?? \App\Models\User::labelForRole($role) }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-500">Le role admin n'est pas disponible pour le responsable scolaire.</p>
            </div>
        </div>

        <div id="student-fields" class="{{ $selectedRole === \App\Models\User::ROLE_STUDENT ? '' : 'hidden' }} space-y-5 rounded-2xl border border-sky-100 bg-sky-50/70 p-4">
            <div>
                <p class="text-sm font-semibold text-slate-900">Dossier Élève</p>
                <p class="mt-1 text-xs text-slate-600">Ces informations lient le compte a un dossier Élève.</p>
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-semibold">Classe</label>
                    <select name="classroom_id" class="app-input">
                        <option value="">Choisir une classe</option>
                        @foreach($classrooms as $classroom)
                            <option value="{{ $classroom->id }}" @selected((string) old('classroom_id', $linkedStudent?->classroom_id) === (string) $classroom->id)>
                                {{ $classroom->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold">Parent lié</label>
                    <select name="parent_user_id" class="app-input">
                        <option value="">Aucun parent lié</option>
                        @foreach($parents as $parent)
                            <option value="{{ $parent->id }}" @selected((string) old('parent_user_id', $linkedStudent?->parent_user_id) === (string) $parent->id)>
                                {{ $parent->name }} - {{ $parent->phone ?: $parent->email }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold">Date de naissance</label>
                    <input name="birth_date" type="date" value="{{ old('birth_date', optional($linkedStudent?->birth_date)->format('Y-m-d')) }}" class="app-input">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold">Genre</label>
                    <select name="gender" class="app-input">
                        <option value="">Non renseigne</option>
                        <option value="male" @selected(old('gender', $linkedStudent?->gender) === 'male')>Garcon</option>
                        <option value="female" @selected(old('gender', $linkedStudent?->gender) === 'female')>Fille</option>
                    </select>
                </div>
            </div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-semibold">{{ $user ? 'Nouveau mot de passe (optionnel)' : 'Mot de passe' }}</label>
            <input id="school_life_user_password" name="password" type="text" class="app-input" placeholder="{{ $user ? 'Laisser vide pour ne pas changer' : 'Minimum 8 caracteres avec lettres et chiffres' }}" @if(!$user) required @endif>
            <x-ui.password-tools target="school_life_user_password" helper="Copiez ce mot de passe pour le communiquer a l'utilisateur." />
        </div>

        <div class="flex items-center justify-end gap-3">
            <x-ui.button :href="route('school-life.users.index')" variant="secondary">Annuler</x-ui.button>
            <x-ui.button type="submit" variant="primary">{{ $submitLabel }}</x-ui.button>
        </div>
    </form>
</section>

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
