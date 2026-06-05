<x-accueil-layout title="Espace accueil" subtitle="Reception des familles, suivi des visiteurs et orientation rapide des demandes.">
    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['label' => 'Visiteurs presents', 'value' => $stats['visitors_inside'], 'pill' => 'bg-sky-50 text-sky-700', 'dot' => 'bg-sky-500'],
            ['label' => 'Visites prevues', 'value' => $stats['visitors_expected'], 'pill' => 'bg-amber-50 text-amber-700', 'dot' => 'bg-amber-500'],
            ['label' => 'Documents a traiter', 'value' => $stats['documents_pending'], 'pill' => 'bg-violet-50 text-violet-700', 'dot' => 'bg-violet-500'],
            ['label' => 'Reclamations ouvertes', 'value' => $stats['feedback_open'], 'pill' => 'bg-emerald-50 text-emerald-700', 'dot' => 'bg-emerald-500'],
        ] as $item)
            <article class="app-card p-5">
                <div class="flex items-center justify-between gap-3">
                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $item['pill'] }}">{{ $item['label'] }}</span>
                    <span class="h-2.5 w-2.5 rounded-full {{ $item['dot'] }}"></span>
                </div>
                <p class="mt-6 text-3xl font-bold text-slate-950">{{ $item['value'] }}</p>
                <p class="mt-1 text-sm text-slate-500">Suivi actualise de l'etablissement.</p>
            </article>
        @endforeach
    </section>

    <section class="app-card p-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="app-overline">Recherche rapide</p>
                <h2 class="mt-2 text-2xl font-semibold text-slate-950">Trouver un eleve ou un parent</h2>
                <p class="mt-2 text-sm text-slate-600">Recherche par nom, classe, email ou telephone pour orienter rapidement les familles.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('accueil.visitors.create') }}" class="app-button-primary">Nouveau visiteur</a>
                <a href="{{ route('accueil.document-requests.index') }}" class="app-button-secondary">Documents</a>
            </div>
        </div>

        <form method="GET" action="{{ route('accueil.dashboard') }}" class="mt-5 flex flex-col gap-3 md:flex-row">
            <input class="app-input md:flex-1" type="search" name="q" value="{{ $q }}" placeholder="Nom eleve, parent, telephone, email ou classe...">
            <button class="app-button-secondary" type="submit">Rechercher</button>
            @if($q !== '')
                <a href="{{ route('accueil.dashboard') }}" class="app-button-ghost">Reset</a>
            @endif
        </form>
    </section>

    <section class="grid gap-6 xl:grid-cols-[1.25fr_0.85fr]">
        <article class="app-card overflow-hidden">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="text-lg font-semibold text-slate-950">Eleves trouves</h3>
                <p class="mt-1 text-sm text-slate-500">Informations utiles pour l'accueil et le contact parent.</p>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse($students as $student)
                    <div class="grid gap-3 px-5 py-4 md:grid-cols-[1fr_0.9fr_0.8fr] md:items-center">
                        <div>
                            <p class="font-semibold text-slate-950">{{ $student->full_name }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ $student->classroom?->name ?? 'Classe non affectee' }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-700">{{ $student->parentUser?->name ?? 'Parent non renseigne' }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $student->parentUser?->email ?? '-' }}</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 md:justify-end">
                            @if($student->parentUser?->phone)
                                <a class="app-button-secondary min-h-9 px-3 py-1.5 text-sm" href="tel:{{ $student->parentUser->phone }}">{{ $student->parentUser->phone }}</a>
                            @else
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-500">Telephone absent</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-sm text-slate-500">Aucun eleve trouve.</div>
                @endforelse
            </div>
        </article>

        <article class="app-card overflow-hidden">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="text-lg font-semibold text-slate-950">Parents</h3>
                <p class="mt-1 text-sm text-slate-500">Contacts rapides lies aux familles.</p>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse($parents as $parent)
                    <div class="px-5 py-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold text-slate-950">{{ $parent->name }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $parent->email ?? '-' }}</p>
                            </div>
                            <span class="rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700">{{ $parent->children_count }} eleve(s)</span>
                        </div>
                        @if($parent->phone)
                            <a class="mt-3 inline-flex text-sm font-semibold text-sky-700 hover:text-sky-800" href="tel:{{ $parent->phone }}">{{ $parent->phone }}</a>
                        @endif
                    </div>
                @empty
                    <div class="p-8 text-center text-sm text-slate-500">Aucun parent trouve.</div>
                @endforelse
            </div>
        </article>
    </section>

    <section class="grid gap-6 xl:grid-cols-3">
        <article class="app-card p-5">
            <div class="flex items-center justify-between gap-3">
                <h3 class="text-lg font-semibold text-slate-950">Derniers visiteurs</h3>
                <a href="{{ route('accueil.visitors.index') }}" class="text-sm font-semibold text-sky-700">Voir tout</a>
            </div>
            <div class="mt-4 space-y-3">
                @forelse($recentVisitors as $visit)
                    <a href="{{ route('accueil.visitors.show', $visit) }}" class="block rounded-2xl border border-slate-200 p-4 transition hover:border-sky-200 hover:bg-sky-50/40">
                        <p class="font-semibold text-slate-950">{{ $visit->visitor_name }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ $visit->purpose_label ?? $visit->purpose }} · {{ $visit->status_label ?? $visit->status }}</p>
                    </a>
                @empty
                    <p class="rounded-2xl border border-dashed border-slate-200 p-5 text-center text-sm text-slate-500">Aucune visite recente.</p>
                @endforelse
            </div>
        </article>

        <article class="app-card p-5">
            <div class="flex items-center justify-between gap-3">
                <h3 class="text-lg font-semibold text-slate-950">Demandes documents</h3>
                <a href="{{ route('accueil.document-requests.index') }}" class="text-sm font-semibold text-sky-700">Voir tout</a>
            </div>
            <div class="mt-4 space-y-3">
                @forelse($recentDocuments as $requestItem)
                    <a href="{{ route('accueil.document-requests.show', $requestItem) }}" class="block rounded-2xl border border-slate-200 p-4 transition hover:border-violet-200 hover:bg-violet-50/40">
                        <p class="font-semibold text-slate-950">{{ $requestItem->type_label }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ $requestItem->student?->full_name ?? '-' }} · {{ $requestItem->parent?->name ?? '-' }}</p>
                    </a>
                @empty
                    <p class="rounded-2xl border border-dashed border-slate-200 p-5 text-center text-sm text-slate-500">Aucune demande recente.</p>
                @endforelse
            </div>
        </article>

        <article class="app-card p-5">
            <div class="flex items-center justify-between gap-3">
                <h3 class="text-lg font-semibold text-slate-950">Reclamations</h3>
                <a href="{{ route('accueil.feedback-cases.index') }}" class="text-sm font-semibold text-sky-700">Voir tout</a>
            </div>
            <div class="mt-4 space-y-3">
                @forelse($recentFeedback as $case)
                    <a href="{{ route('accueil.feedback-cases.show', $case) }}" class="block rounded-2xl border border-slate-200 p-4 transition hover:border-emerald-200 hover:bg-emerald-50/40">
                        <p class="font-semibold text-slate-950">{{ $case->reference }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ \Illuminate\Support\Str::limit($case->subject, 70) }}</p>
                    </a>
                @empty
                    <p class="rounded-2xl border border-dashed border-slate-200 p-5 text-center text-sm text-slate-500">Aucune reclamation recente.</p>
                @endforelse
            </div>
        </article>
    </section>
</x-accueil-layout>
