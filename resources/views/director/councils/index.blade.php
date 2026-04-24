<x-director-layout title="Conseil de classe">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Conseil de classe</h1>
            <p class="mt-1 text-sm text-slate-500">Décisions, recommandations et suivi par classe et période.</p>
        </div>

        <a href="{{ route('director.councils.create') }}"
           class="rounded-2xl bg-black px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-900">
            + Nouvelle décision
        </a>
    </div>

    @if(session('success'))
        <div class="mt-6 rounded-3xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-900">
            {{ session('success') }}
        </div>
    @endif

    <div class="mt-6 overflow-hidden rounded-[28px] border border-black/10 bg-white/80 shadow-sm">
        <div class="border-b border-black/10 bg-white/60 px-5 py-4 flex items-center justify-between">
            <div class="text-sm font-semibold text-slate-900">Historique</div>
            <div class="text-xs text-slate-500">Éléments : <span class="font-semibold text-slate-900">{{ count($items ?? []) }}</span></div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50/80">
                    <tr class="text-left border-b border-black/5">
                        <th class="p-4 text-xs font-semibold text-slate-500">Classe</th>
                        <th class="p-4 text-xs font-semibold text-slate-500">Période</th>
                        <th class="p-4 text-xs font-semibold text-slate-500">Décision</th>
                        <th class="p-4 text-xs font-semibold text-slate-500">Date</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-black/5">
                    @forelse(($items ?? []) as $it)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="p-4 font-semibold text-slate-900">{{ $it->classroom?->name ?? $it->classroom_name ?? '—' }}</td>
                            <td class="p-4 text-slate-700">{{ $it->period ?? '—' }}</td>
                            <td class="p-4 text-slate-700">
                                <div class="line-clamp-2">{{ $it->decision ?? '—' }}</div>
                            </td>
                            <td class="p-4 text-slate-700">
                                {{ optional($it->held_at)->format('d/m/Y') ?? ($it->held_at ? \Carbon\Carbon::parse($it->held_at)->format('d/m/Y') : '—') }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="p-10 text-center text-slate-500">Aucune décision enregistrée.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-director-layout>
