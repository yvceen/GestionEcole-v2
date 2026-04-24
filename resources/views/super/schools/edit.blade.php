<x-super-layout
    title="Modifier une ecole"
    subtitle="Mettez a jour les informations de l'etablissement, l'acces et le compte administrateur associe."
>
    @php
        $logoPath = $school->logo_path;
        $logoUrl = $logoPath && Storage::disk('public')->exists(ltrim($logoPath, '/'))
            ? asset('storage/' . ltrim($logoPath, '/'))
            : asset('images/edulogo.jpg');
    @endphp

    <x-page-header
        title="Modifier {{ $school->name }}"
        subtitle="Les changements appliques ici mettent a jour l'ecole et, si renseigne, son administrateur principal."
        eyebrow="Edition"
    >
        <x-ui.button :href="route('super.schools.index')" variant="secondary">
            Retour a la liste
        </x-ui.button>
        <x-ui.button type="submit" form="school-edit-form" variant="primary">
            Enregistrer
        </x-ui.button>
    </x-page-header>

    @if($errors->any())
        <x-ui.alert variant="error">
            <p class="font-semibold">Le formulaire contient des erreurs.</p>
            <ul class="mt-2 list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-ui.alert>
    @endif

    <form id="school-edit-form" method="POST" action="{{ route('super.schools.update', $school) }}" enctype="multipart/form-data" class="super-form-grid">
        @csrf
        @method('PUT')

        <div class="super-form-stack">
            <x-super.panel
                title="Informations de l'ecole"
                subtitle="Modifiez l'identite, le slug et les informations exposees a l'ensemble de la plateforme."
            >
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div class="app-field md:col-span-2">
                        <label for="name" class="app-label">Nom de l ecole</label>
                        <input id="name" name="name" value="{{ old('name', $school->name) }}" class="app-input" required>
                        @error('name')
                            <p class="app-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="app-field md:col-span-2">
                        <label for="slug" class="app-label">Slug</label>
                        <input id="slug" name="slug" value="{{ old('slug', $school->slug) }}" class="app-input">
                        <p class="app-hint">Laissez la valeur actuelle si vous ne souhaitez pas changer l identifiant technique.</p>
                        @error('slug')
                            <p class="app-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </x-super.panel>

            <x-super.panel
                title="Administrateur principal"
                subtitle="Les champs ci-dessous mettent a jour le compte admin relie a cette ecole."
            >
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div class="app-field">
                        <label for="admin_name" class="app-label">Nom</label>
                        <input id="admin_name" name="admin_name" value="{{ old('admin_name', $admin?->name) }}" class="app-input">
                        @error('admin_name')
                            <p class="app-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="app-field">
                        <label for="admin_email" class="app-label">Email</label>
                        <input id="admin_email" type="email" name="admin_email" value="{{ old('admin_email', $admin?->email) }}" class="app-input">
                        @error('admin_email')
                            <p class="app-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="app-field md:col-span-2">
                        <label for="admin_password" class="app-label">Nouveau mot de passe</label>
                        <input id="admin_password" type="password" name="admin_password" class="app-input">
                        <p class="app-hint">Laissez vide pour conserver le mot de passe actuel.</p>
                        @error('admin_password')
                            <p class="app-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </x-super.panel>
        </div>

        <aside class="super-aside-stack">
            <x-super.panel
                title="Etat de l'ecole"
                subtitle="Controlez l'acces de cet espace pour ses utilisateurs."
            >
                <label class="flex items-start justify-between gap-4 rounded-[22px] border border-slate-200 bg-slate-50/75 px-4 py-4">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">Ecole active</p>
                        <p class="mt-1 text-sm text-slate-500">Desactivez uniquement si vous souhaitez bloquer temporairement l acces a cet espace.</p>
                    </div>
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $school->is_active)) class="mt-1 h-5 w-5 rounded border-slate-300 text-sky-700 focus:ring-sky-200">
                </label>
            </x-super.panel>

            <x-super.panel
                title="Branding"
                subtitle="Visualisez le logo actuel et remplacez-le si necessaire."
            >
                <div class="space-y-4">
                    <div class="flex items-center gap-4 rounded-[22px] border border-slate-200 bg-slate-50/75 p-4">
                        <div class="h-16 w-16 overflow-hidden rounded-2xl border border-slate-200 bg-white">
                            <img src="{{ $logoUrl }}" alt="Logo {{ $school->name }}" class="h-full w-full object-cover">
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-900">Logo actuel</p>
                            <p class="mt-1 text-sm text-slate-500">Chargez un nouveau fichier pour le remplacer.</p>
                        </div>
                    </div>

                    <div class="app-field">
                        <label for="logo" class="app-label">Nouveau logo</label>
                        <input id="logo" type="file" name="logo" accept="image/*" class="app-input file:mr-4 file:rounded-full file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-slate-700">
                        <p class="app-hint">Formats acceptes : PNG ou JPG, 2 Mo maximum.</p>
                        @error('logo')
                            <p class="app-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </x-super.panel>

            <x-super.panel
                title="Recapitulatif"
                subtitle="Contexte utile avant validation."
            >
                <dl class="super-detail-list">
                    <div class="super-detail-item">
                        <dt>Slug actuel</dt>
                        <dd>{{ $school->slug }}</dd>
                    </div>
                    <div class="super-detail-item">
                        <dt>Sous-domaine</dt>
                        <dd>{{ $school->subdomain }}</dd>
                    </div>
                    <div class="super-detail-item">
                        <dt>URL de l ecole</dt>
                        <dd class="break-all">{{ $school->appUrl() }}</dd>
                    </div>
                    <div class="super-detail-item">
                        <dt>Statut actuel</dt>
                        <dd>{{ $school->is_active ? 'Active' : 'Inactive' }}</dd>
                    </div>
                    <div class="super-detail-item">
                        <dt>Admin associe</dt>
                        <dd>{{ $admin?->email ?? 'Aucun admin' }}</dd>
                    </div>
                </dl>

                <div class="flex flex-wrap gap-3 pt-2">
                    <x-ui.button :href="route('super.schools.index')" variant="secondary">
                        Annuler
                    </x-ui.button>
                    <x-ui.button type="submit" form="school-edit-form" variant="primary">
                        Enregistrer
                    </x-ui.button>
                </div>
            </x-super.panel>
        </aside>
    </form>
</x-super-layout>
