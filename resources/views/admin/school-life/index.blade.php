{{-- resources/views/admin/school-life/index.blade.php --}}
<x-admin-layout title="Vie scolaire">
    <x-slot name="header">Vie scolaire</x-slot>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-slate-900">Vie scolaire</h1>
                <p class="mt-1 text-sm text-slate-500">Suivre les infos et activites de l'etablissement.</p>
            </div>
            <a href="{{ route('admin.school-life.create') }}"
               class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-black">
                + Nouvelle entree
            </a>
        </div>

        @if(session('success'))
            <div class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800 text-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="mt-6 rounded-[28px] border border-black/5 bg-white/80 backdrop-blur-xl p-4 shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wider text-slate-500">
                            <th class="py-3 px-3">Titre</th>
                            <th class="py-3 px-3">Statut</th>
                            <th class="py-3 px-3">Date</th>
                            <th class="py-3 px-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-black/5">
                        @forelse($items as $item)
                            <tr>
                                <td class="py-3 px-3 font-semibold text-slate-900">{{ $item->title }}</td>
                                <td class="py-3 px-3 text-slate-600">{{ $item->status }}</td>
                                <td class="py-3 px-3 text-slate-600">{{ optional($item->date)->format('d/m/Y') }}</td>
                                <td class="py-3 px-3">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('admin.school-life.edit', $item) }}"
                                           class="rounded-xl border border-black/10 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                            Modifier
                                        </a>
                                        <form method="POST" action="{{ route('admin.school-life.destroy', $item) }}"
                                              onsubmit="return confirm('Supprimer cette entree ?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100">
                                                Supprimer
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-6 text-center text-slate-500">Aucune entree.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6">
            {{ $items->links() }}
        </div>
    </div>
</x-admin-layout>
