<x-admin-layout title="Parents">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Parents</h1>
            <p class="mt-1 text-sm text-slate-500">Gerez les frais des familles et accedez rapidement aux plans par eleve.</p>
        </div>

        <form method="GET" class="flex items-center gap-2">
            <input type="text"
                   name="q"
                   value="{{ $q }}"
                   placeholder="Rechercher par nom, email ou telephone..."
                   class="w-64 rounded-2xl border-slate-200 bg-white/70 px-3 py-2 text-sm focus:border-slate-900 focus:ring-slate-900" />
            <button class="rounded-2xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-black transition">
                Rechercher
            </button>
        </form>
    </div>

    <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-left">
                        <th class="p-4">Parent</th>
                        <th class="p-4">Email</th>
                        <th class="p-4">Enfants</th>
                        <th class="p-4"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($parents as $parent)
                        <tr class="border-t border-slate-100 hover:bg-slate-50">
                            <td class="p-4 font-semibold text-slate-900">{{ $parent->name }}</td>
                            <td class="p-4 text-slate-700">{{ $parent->email }}</td>
                            <td class="p-4 text-slate-700">{{ $childrenCounts[$parent->id] ?? 0 }}</td>
                            <td class="p-4 text-right">
                                    <a href="{{ route('admin.parents.fees.edit', $parent) }}"
                                       class="rounded-2xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-800 hover:bg-slate-50 transition">
                                    Gerer les frais
                                </a>
                            </td>
                        </tr>
                    @endforeach

                    @if($parents->count() === 0)
                        <tr>
                            <td colspan="4" class="p-8 text-center text-slate-500">Aucun resultat trouve.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">
        {{ $parents->links() }}
    </div>
</x-admin-layout>
