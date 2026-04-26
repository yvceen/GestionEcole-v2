<x-admin-layout title="Frais parent">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Frais - {{ $parent->name }}</h1>
            <p class="mt-1 text-sm text-slate-500">Renseignez les montants mensuels et annuels par eleve sans quitter ce module.</p>
        </div>

        <a href="{{ route('admin.parents.index') }}"
           class="rounded-2xl border border-slate-200 bg-white/70 px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-white transition">
            Retour
        </a>
    </div>

    @if(session('success'))
        <div class="mt-6 rounded-3xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-900 shadow-sm">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mt-6 rounded-3xl border border-red-200 bg-red-50 p-4 text-red-900">
            <ul class="list-disc ml-5">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.parents.fees.update', $parent) }}" class="mt-6">
        @csrf
        @method('PUT')

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 p-4">
                <div class="text-sm font-semibold text-slate-900">Eleves</div>
                <div class="text-xs text-slate-500">Renseignez les montants puis enregistrez.</div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left">
                            <th class="p-4">Eleve</th>
                            <th class="p-4">Classe</th>
                            <th class="p-4">Scolarite /mois</th>
                            <th class="p-4">Transport /mois</th>
                            <th class="p-4">Cantine /mois</th>
                            <th class="p-4">Assurance /an</th>
                            <th class="p-4">Debut (mois)</th>
                            <th class="p-4">Note</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($students as $s)
                            @php
                                $f = $fees[$s->id] ?? null;
                            @endphp
                            <tr class="border-t border-slate-100 hover:bg-slate-50">
                                <td class="p-4 font-semibold text-slate-900">
                                    {{ $s->full_name }}
                                    <div class="text-xs text-slate-500">ID: {{ $s->id }}</div>
                                </td>

                                <td class="p-4 text-slate-700">
                                    {{ $s->classroom?->name ?? '-' }}
                                </td>

                                <td class="p-4">
                                    <input type="number" step="0.01"
                                           name="fees[{{ $s->id }}][tuition_monthly]"
                                           value="{{ old("fees.$s->id.tuition_monthly", $f->tuition_monthly ?? 0) }}"
                                           class="w-32 rounded-xl border-slate-200 bg-white/70 focus:border-slate-900 focus:ring-slate-900" />
                                </td>

                                <td class="p-4">
                                    <input type="number" step="0.01"
                                           name="fees[{{ $s->id }}][transport_monthly]"
                                           value="{{ old("fees.$s->id.transport_monthly", $f->transport_monthly ?? 0) }}"
                                           class="w-32 rounded-xl border-slate-200 bg-white/70 focus:border-slate-900 focus:ring-slate-900" />
                                </td>

                                <td class="p-4">
                                    <input type="number" step="0.01"
                                           name="fees[{{ $s->id }}][canteen_monthly]"
                                           value="{{ old("fees.$s->id.canteen_monthly", $f->canteen_monthly ?? 0) }}"
                                           class="w-32 rounded-xl border-slate-200 bg-white/70 focus:border-slate-900 focus:ring-slate-900" />
                                </td>

                                <td class="p-4">
                                    <input type="number" step="0.01"
                                           name="fees[{{ $s->id }}][insurance_yearly]"
                                           value="{{ old("fees.$s->id.insurance_yearly", $f->insurance_yearly ?? 0) }}"
                                           class="w-32 rounded-xl border-slate-200 bg-white/70 focus:border-slate-900 focus:ring-slate-900" />
                                </td>

                                <td class="p-4">
                                    <input type="number" min="1" max="12"
                                           name="fees[{{ $s->id }}][starts_month]"
                                           value="{{ old("fees.$s->id.starts_month", $f->starts_month ?? 9) }}"
                                           class="w-24 rounded-xl border-slate-200 bg-white/70 focus:border-slate-900 focus:ring-slate-900" />
                                </td>

                                <td class="p-4">
                                    <input type="text"
                                           name="fees[{{ $s->id }}][notes]"
                                           value="{{ old("fees.$s->id.notes", $f->notes ?? '') }}"
                                           placeholder="Optionnel"
                                           class="w-56 rounded-xl border-slate-200 bg-white/70 focus:border-slate-900 focus:ring-slate-900" />
                                </td>
                            </tr>
                        @endforeach

                        @if($students->count() === 0)
                            <tr>
                                <td colspan="8" class="p-8 text-center text-slate-500">Aucun eleve lie a ce parent.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <div class="p-4 flex items-center justify-end gap-3">
                <a href="{{ route('admin.parents.index') }}"
                   class="rounded-2xl border border-slate-200 bg-white/70 px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-white transition">
                    Annuler
                </a>
                <button class="rounded-2xl bg-black px-5 py-2 text-sm font-semibold text-white hover:bg-slate-900 transition">
                    Enregistrer
                </button>
            </div>
        </div>
    </form>
</x-admin-layout>
