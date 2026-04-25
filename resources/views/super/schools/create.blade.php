<x-super-layout
    title="Nouvelle ecole"
    subtitle="Creez un nouvel espace, associez son administrateur principal et preparez son ouverture."
>
    @php
        $domainService = app(\App\Services\SchoolDomainService::class);
        $primaryDomain = $domainService->primaryDomain();
        $initialPreview = old('school_name')
            ? $domainService->normalizeSubdomain((string) old('school_name'))
            : 'nom-ecole';
    @endphp

    <x-page-header
        title="Nouvelle ecole"
        subtitle="La creation prepare l ecole, son compte administrateur et son adresse d acces."
        eyebrow="Onboarding"
    >
        <x-ui.button :href="route('super.schools.index')" variant="secondary">
            Retour a la liste
        </x-ui.button>
        <x-ui.button type="submit" form="school-create-form" variant="primary">
            Creer l'ecole
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

    <form id="school-create-form" method="POST" action="{{ route('super.schools.store') }}" enctype="multipart/form-data" class="super-form-grid">
        @csrf

        <div class="super-form-stack">
            <x-super.panel
                title="Informations de l'ecole"
                subtitle="L adresse d acces est proposee automatiquement a partir du nom. Le nom court reste facultatif."
            >
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div class="app-field md:col-span-2">
                        <label for="school_name" class="app-label">Nom de l ecole</label>
                        <input id="school_name" name="school_name" value="{{ old('school_name') }}" class="app-input" placeholder="Groupe scolaire Marie Curie" required>
                        <p class="app-hint">Visible dans l interface, les emails et utilise pour proposer l adresse d acces.</p>
                        @error('school_name')
                            <p class="app-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="app-field md:col-span-2">
                        <label for="slug" class="app-label">Nom court (optionnel)</label>
                        <input id="slug" name="slug" value="{{ old('slug') }}" class="app-input" placeholder="marie-curie">
                        <p class="app-hint">Optionnel. Permet de definir un libelle court et stable pour l etablissement.</p>
                        @error('slug')
                            <p class="app-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2 rounded-[24px] border border-slate-200 bg-slate-50/80 px-5 py-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Adresse d acces proposee</p>
                        <p class="mt-2 text-lg font-semibold text-slate-950">
                            <span id="subdomain-preview">{{ $initialPreview !== '' ? $initialPreview : 'nom-ecole' }}</span>.{{ $primaryDomain }}
                        </p>
                        <p class="mt-1 text-sm text-slate-500">Le systeme ajuste automatiquement le nom propose si celui-ci est deja utilise.</p>
                    </div>
                </div>
            </x-super.panel>

            <x-super.panel
                title="Administrateur principal"
                subtitle="Ce compte recevra l'acces initial a l'ecole et pourra prendre la main localement."
            >
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div class="app-field">
                        <label for="admin_name" class="app-label">Nom</label>
                        <input id="admin_name" name="admin_name" value="{{ old('admin_name') }}" class="app-input" required>
                        @error('admin_name')
                            <p class="app-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="app-field">
                        <label for="admin_email" class="app-label">Email</label>
                        <input id="admin_email" type="email" name="admin_email" value="{{ old('admin_email') }}" class="app-input" placeholder="admin@ecole.com" required>
                        @error('admin_email')
                            <p class="app-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="app-field md:col-span-2">
                        <label for="admin_password" class="app-label">Mot de passe initial</label>
                        <input id="admin_password" type="password" name="admin_password" class="app-input" required>
                        <p class="app-hint">Minimum 6 caracteres. L'equipe locale pourra le modifier ensuite.</p>
                        @error('admin_password')
                            <p class="app-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </x-super.panel>
        </div>

        <aside class="super-aside-stack">
            <x-super.panel
                title="Activation"
                subtitle="Choisissez si l'ecole doit etre ouverte des sa creation."
            >
                <label class="flex items-start justify-between gap-4 rounded-[22px] border border-slate-200 bg-slate-50/75 px-4 py-4">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">Activer des maintenant</p>
                        <p class="mt-1 text-sm text-slate-500">Si active, l administrateur pourra se connecter immediatement sur la nouvelle adresse d acces.</p>
                    </div>
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true)) class="mt-1 h-5 w-5 rounded border-slate-300 text-sky-700 focus:ring-sky-200">
                </label>
            </x-super.panel>

            <x-super.panel
                title="Branding"
                subtitle="Ajoutez le logo qui sera affiche sur les recus et ecrans de l'ecole."
            >
                <div class="app-field">
                    <label for="logo" class="app-label">Logo</label>
                    <input id="logo" type="file" name="logo" accept="image/*" class="app-input file:mr-4 file:rounded-full file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-slate-700">
                    <p class="app-hint">Formats acceptes : PNG ou JPG, 2 Mo maximum.</p>
                    @error('logo')
                        <p class="app-error">{{ $message }}</p>
                    @enderror
                </div>
            </x-super.panel>

            <x-super.panel
                title="Recapitulatif"
                subtitle="Controle rapide avant lancement."
            >
                <dl class="super-detail-list">
                    <div class="super-detail-item">
                        <dt>Elements crees</dt>
                        <dd>1 ecole + 1 admin</dd>
                    </div>
                    <div class="super-detail-item">
                        <dt>Redirection finale</dt>
                        <dd>Nouvelle adresse d acces de l ecole</dd>
                    </div>
                    <div class="super-detail-item">
                        <dt>Adresse d acces</dt>
                        <dd><span id="subdomain-preview-side">{{ $initialPreview !== '' ? $initialPreview : 'nom-ecole' }}</span>.{{ $primaryDomain }}</dd>
                    </div>
                    <div class="super-detail-item">
                        <dt>Acces initial</dt>
                        <dd>{{ old('is_active', true) ? 'Ouvert' : 'Desactive' }}</dd>
                    </div>
                </dl>

                <div class="flex flex-wrap gap-3 pt-2">
                    <x-ui.button :href="route('super.schools.index')" variant="secondary">
                        Annuler
                    </x-ui.button>
                    <x-ui.button type="submit" form="school-create-form" variant="primary">
                        Creer l'ecole
                    </x-ui.button>
                </div>
            </x-super.panel>
        </aside>
    </form>

    <script>
        (function () {
            const nameInput = document.getElementById('school_name');
            const preview = document.getElementById('subdomain-preview');
            const previewSide = document.getElementById('subdomain-preview-side');

            function normalize(value) {
                return value
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .toLowerCase()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/-+/g, '-')
                    .replace(/^-|-$/g, '') || 'nom-ecole';
            }

            function syncPreview() {
                const value = normalize(nameInput?.value || '');
                if (preview) preview.textContent = value;
                if (previewSide) previewSide.textContent = value;
            }

            if (nameInput) {
                nameInput.addEventListener('input', syncPreview);
                syncPreview();
            }
        })();
    </script>
</x-super-layout>
