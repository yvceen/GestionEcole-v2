<x-admin-layout title="Matieres">
    <div class="mx-auto w-full max-w-7xl space-y-5">
        <section class="app-card px-5 py-5 md:px-6">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                <div class="max-w-3xl">
                    <p class="app-overline">Pilotage pedagogique</p>
                    <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">Matieres</h1>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Centralisez les matieres, leurs statuts et les affectations enseignants dans une interface plus stable et plus lisible.
                    </p>
                </div>

                <a href="{{ route('admin.subjects.create') }}"
                   class="inline-flex min-h-11 items-center justify-center rounded-2xl bg-black px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-900">
                    Nouvelle matiere
                </a>
            </div>

            <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <article class="app-stat-card min-h-[9.5rem]">
                    <p class="app-stat-label">Total</p>
                    <p class="app-stat-value">{{ $stats['total'] ?? 0 }}</p>
                    <p class="app-stat-meta">Matieres configurees.</p>
                </article>
                <article class="app-stat-card min-h-[9.5rem]">
                    <p class="app-stat-label">Actives</p>
                    <p class="app-stat-value text-emerald-700">{{ $stats['active'] ?? 0 }}</p>
                    <p class="app-stat-meta">Disponibles pour le flux scolaire.</p>
                </article>
                <article class="app-stat-card min-h-[9.5rem]">
                    <p class="app-stat-label">Inactives</p>
                    <p class="app-stat-value text-slate-700">{{ $stats['inactive'] ?? 0 }}</p>
                    <p class="app-stat-meta">Cachees sans suppression.</p>
                </article>
                <article class="app-stat-card min-h-[9.5rem]">
                    <p class="app-stat-label">Affectees</p>
                    <p class="app-stat-value text-sky-700">{{ $stats['assigned'] ?? 0 }}</p>
                    <p class="app-stat-meta">Au moins un enseignant assigne.</p>
                </article>
            </div>
        </section>

        @if(session('success'))
            <div class="rounded-3xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 shadow-sm">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="rounded-3xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900 shadow-sm">
                {{ session('error') }}
            </div>
        @endif

        <section class="app-card px-5 py-5 md:px-6">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold text-slate-950">Recherche et filtres</h2>
                    <p class="mt-1 text-sm text-slate-500">Affinez rapidement la liste sans casser la lisibilite de la page.</p>
                </div>
                <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">
                    {{ $subjects->total() }} resultat(s)
                </div>
            </div>

            <form method="GET" class="mt-5 grid gap-4 xl:grid-cols-[minmax(0,1fr)_220px_auto] xl:items-end">
                <input name="q" value="{{ $q ?? '' }}" placeholder="Nom, code ou enseignant..."
                       class="min-h-11 rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm text-slate-700 shadow-sm outline-none transition focus:border-slate-300 focus:ring-2 focus:ring-slate-200">

                <select name="status" class="min-h-11 rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm text-slate-700 shadow-sm outline-none transition focus:border-slate-300 focus:ring-2 focus:ring-slate-200">
                    <option value="all" @selected(($status ?? 'all') === 'all')>Tous les statuts</option>
                    <option value="active" @selected(($status ?? 'all') === 'active')>Actives</option>
                    <option value="inactive" @selected(($status ?? 'all') === 'inactive')>Inactives</option>
                </select>

                <div class="flex flex-col gap-3 sm:flex-row xl:justify-end">
                    <button class="inline-flex min-h-11 items-center justify-center rounded-2xl bg-black px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-900">
                        Filtrer
                    </button>
                    <a href="{{ route('admin.subjects.index') }}"
                       class="inline-flex min-h-11 items-center justify-center rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50">
                        Reinitialiser
                    </a>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-[30px] border border-black/10 bg-white shadow-[0_24px_64px_-40px_rgba(15,23,42,0.35)]">
            <div class="flex flex-col gap-3 border-b border-black/10 bg-slate-50/90 px-5 py-4 md:flex-row md:items-center md:justify-between md:px-6">
                <div class="min-w-0">
                    <h3 class="text-base font-semibold text-slate-950">Liste des matieres</h3>
                    <p class="mt-1 text-sm text-slate-500">Vue principale des matieres, de leur activation et des affectations enseignants.</p>
                </div>
                <div class="inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600 shadow-sm">
                    Total : <span class="ml-1 text-slate-900">{{ $subjects->total() }}</span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full table-fixed border-separate border-spacing-0 text-sm">
                    <colgroup>
                        <col class="w-[38%]">
                        <col class="w-[18%]">
                        <col class="w-[24%]">
                        <col class="w-[20%]">
                    </colgroup>
                    <thead class="bg-slate-50/80">
                    <tr class="border-b border-black/5 text-left text-[0.72rem] font-semibold uppercase tracking-[0.16em] text-slate-500">
                        <th class="px-6 py-4">Matiere</th>
                        <th class="px-6 py-4">Statut</th>
                        <th class="px-6 py-4">Affectation enseignants</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="align-top">
                    @forelse($subjects as $subject)
                        <tr class="border-t border-black/5 transition hover:bg-slate-50/85">
                            <td class="px-6 py-5">
                                <div class="min-w-0">
                                    <p class="text-base font-semibold text-slate-950">{{ $subject->name }}</p>
                                    <p class="mt-1 text-sm text-slate-500">
                                        {{ $subject->code ?: 'Aucun code' }}
                                    </p>
                                </div>
                            </td>
                            <td class="px-6 py-5">
                                <span class="inline-flex min-h-8 items-center rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-[0.12em]
                                    {{ $subject->is_active ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-slate-200 bg-slate-100 text-slate-700' }}">
                                    {{ $subject->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-5">
                                <div class="inline-flex items-center rounded-2xl border border-sky-100 bg-sky-50 px-3 py-2 text-sm font-semibold text-sky-800">
                                    {{ (int) $subject->teachers_count }} enseignant(s)
                                </div>
                            </td>
                            <td class="px-6 py-5">
                                <div class="flex justify-end">
                                    <details class="relative">
                                        <summary class="flex h-10 w-10 cursor-pointer list-none items-center justify-center rounded-xl border border-black/10 bg-white text-slate-600 shadow-sm transition hover:bg-slate-50">
                                            <span class="sr-only">Actions</span>
                                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path d="M10 4a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm0 7.5a1.5 1.5 0 110-3 1.5 1.5 0 010 3zM11.5 17a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                                            </svg>
                                        </summary>
                                        <div class="absolute right-0 z-20 mt-2 w-44 rounded-2xl border border-slate-200 bg-white p-2 shadow-[0_18px_48px_-24px_rgba(15,23,42,0.3)]">
                                            <div class="flex flex-col gap-2">
                                                <a href="{{ route('admin.subjects.edit', $subject) }}"
                                                   class="inline-flex min-h-10 items-center justify-start rounded-xl border border-black/10 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                                    Gerer
                                                </a>

                                                <form method="POST" action="{{ route('admin.subjects.destroy', $subject) }}"
                                                      onsubmit="return confirm('Supprimer cette matiere ?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="inline-flex min-h-10 w-full items-center justify-start rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-100">
                                                        Supprimer
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </details>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-14 text-center">
                                <div class="mx-auto max-w-md">
                                    <p class="text-base font-semibold text-slate-900">Aucune matiere trouvee</p>
                                    <p class="mt-2 text-sm text-slate-500">Ajustez les filtres ou creez une nouvelle matiere pour alimenter la liste.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-black/5 px-5 py-4 md:px-6">
                {{ $subjects->links() }}
            </div>
        </section>
    </div>
</x-admin-layout>
