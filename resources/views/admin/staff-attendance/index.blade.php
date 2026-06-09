<x-admin-layout title="Présence personnel" subtitle="Suivez les pointages du personnel importés depuis ZKBioTime avec une lecture claire par jour, département et utilisateur.">
    <x-ui.page-header
        title="Présence personnel"
        subtitle="Pointages importés automatiquement depuis la pointeuse. Les doublons sont filtrés par code employé, date, heure et terminal."
    >
        <x-slot name="actions">
            <x-ui.button :href="route('admin.staff-attendance.mappings')" variant="secondary">
                Correspondances
            </x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <section class="grid gap-4 md:grid-cols-4">
        <div class="rounded-[24px] border border-sky-100 bg-sky-50 px-5 py-5">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-sky-700">Pointages</p>
            <p class="mt-3 text-3xl font-bold text-slate-950">{{ $stats['logs'] }}</p>
            <p class="mt-1 text-sm text-slate-500">Mouvements du jour</p>
        </div>
        <div class="rounded-[24px] border border-emerald-100 bg-emerald-50 px-5 py-5">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-700">Personnel</p>
            <p class="mt-3 text-3xl font-bold text-slate-950">{{ $stats['employees'] }}</p>
            <p class="mt-1 text-sm text-slate-500">Codes uniques</p>
        </div>
        <div class="rounded-[24px] border border-indigo-100 bg-indigo-50 px-5 py-5">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-indigo-700">Reliés</p>
            <p class="mt-3 text-3xl font-bold text-slate-950">{{ $stats['mapped'] }}</p>
            <p class="mt-1 text-sm text-slate-500">Comptes My Edu</p>
        </div>
        <div class="rounded-[24px] border border-amber-100 bg-amber-50 px-5 py-5">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-amber-700">À relier</p>
            <p class="mt-3 text-3xl font-bold text-slate-950">{{ $stats['unmapped'] }}</p>
            <p class="mt-1 text-sm text-slate-500">Codes non associés</p>
        </div>
    </section>

    <section class="mt-6 rounded-[28px] border border-slate-200 bg-white px-5 py-5 shadow-sm">
        <form method="GET" action="{{ route('admin.staff-attendance.index') }}" class="grid gap-3 lg:grid-cols-[170px_220px_180px_minmax(0,1fr)_auto] lg:items-end">
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-800">Date</label>
                <input type="date" name="date" value="{{ $date }}" class="app-input">
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-800">Département</label>
                <select name="department" class="app-input">
                    <option value="">Tous</option>
                    @foreach($departments as $item)
                        <option value="{{ $item }}" @selected($department === $item)>{{ $item }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-800">Lien utilisateur</label>
                <select name="mapped" class="app-input">
                    <option value="">Tous</option>
                    <option value="yes" @selected($mapped === 'yes')>Reliés</option>
                    <option value="no" @selected($mapped === 'no')>Non reliés</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-800">Recherche</label>
                <input name="q" value="{{ $q }}" class="app-input" placeholder="Nom, code, département...">
            </div>
            <div class="flex gap-2">
                <x-ui.button type="submit" variant="primary">Filtrer</x-ui.button>
                <x-ui.button :href="route('admin.staff-attendance.index')" variant="secondary">Reset</x-ui.button>
            </div>
        </form>
    </section>

    <section class="mt-6 overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-6 py-5">
            <h2 class="text-xl font-semibold tracking-tight text-slate-950">Journal des pointages</h2>
            <p class="mt-1 text-sm text-slate-500">Chaque ligne représente un passage détecté par la pointeuse.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">
                    <tr>
                        <th class="px-6 py-4">Heure</th>
                        <th class="px-6 py-4">Employé</th>
                        <th class="px-6 py-4">Département</th>
                        <th class="px-6 py-4">Terminal</th>
                        <th class="px-6 py-4">Compte My Edu</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($logs as $log)
                        <tr class="hover:bg-sky-50/40">
                            <td class="whitespace-nowrap px-6 py-4 font-semibold text-slate-900">
                                {{ optional($log->punched_at)->format('H:i:s') }}
                                <span class="block text-xs font-medium text-slate-500">{{ optional($log->punched_at)->format('d/m/Y') }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-semibold text-slate-900">{{ $log->employee_name ?: trim(($log->first_name ?? '') . ' ' . ($log->last_name ?? '')) ?: 'Employé ZKBioTime' }}</p>
                                <p class="text-xs text-slate-500">Code {{ $log->employee_code }}</p>
                            </td>
                            <td class="px-6 py-4 text-slate-600">{{ $log->department_name ?: '-' }}</td>
                            <td class="px-6 py-4 text-slate-600">
                                {{ $log->terminal_alias ?: 'Pointeuse' }}
                                <span class="block text-xs text-slate-400">{{ $log->terminal_sn }}</span>
                            </td>
                            <td class="px-6 py-4">
                                @if($log->user)
                                    <x-ui.badge variant="success">{{ $log->user->name }}</x-ui.badge>
                                @else
                                    <x-ui.badge variant="warning">À relier</x-ui.badge>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-500">Aucun pointage trouvé pour cette sélection.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="mt-6">
        {{ $logs->links() }}
    </div>
</x-admin-layout>
