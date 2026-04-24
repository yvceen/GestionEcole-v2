<x-admin-layout :title="'Classe: '.$classroom->name">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">{{ $classroom->name }}</h1>
            <p class="mt-1 text-sm text-slate-500">
                Niveau: <span class="font-semibold text-slate-800">{{ $classroom->level->name }}</span>
                <span class="mx-2 text-slate-300">|</span>
                Section: <span class="font-semibold text-slate-800">{{ $classroom->section }}</span>
            </p>
        </div>
        <a href="{{ route('admin.structure.index') }}" class="text-sm font-semibold text-slate-700 hover:underline">Retour</a>
    </div>

    <div class="mt-8 rounded-[28px] border border-black/5 bg-white p-6 shadow-[0_18px_45px_-30px_rgba(0,0,0,.35)]">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-lg font-semibold text-slate-900">Eleves dans cette classe</h2>
            <div class="text-sm text-slate-500">
                Total: <span class="font-semibold text-slate-900">{{ $students->count() }}</span>
            </div>
        </div>

        <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200/70">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="text-left px-4 py-3 font-semibold">Eleve</th>
                            <th class="text-left px-4 py-3 font-semibold">Parent</th>
                            <th class="text-left px-4 py-3 font-semibold">Date naissance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/70">
                        @forelse($students as $s)
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-4 py-3 font-semibold text-slate-900">{{ $s->full_name }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $s->parentUser?->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $s->birth_date ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-6 text-center text-slate-500">
                                    Aucun eleve dans cette classe.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-admin-layout>
