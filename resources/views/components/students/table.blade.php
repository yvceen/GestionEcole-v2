@props([
    'students',
    'classrooms',
    'classroomId' => null,
    'q' => null,
    'status' => 'active',
])

@php use Illuminate\Support\Facades\Route; @endphp

<div
    x-data="{
        toastOpen: {{ session('success') ? 'true' : 'false' }},
        toastMessage: @js(session('success')),
        deleteAction: '',
        deleteName: '',
        openConfirm(action, name) { this.deleteAction = action; this.deleteName = name; },
    }"
    x-init="if (toastOpen) { setTimeout(() => toastOpen = false, 3500) }"
    x-on:keydown.escape.window="deleteAction = ''"
    class="space-y-5"
>
    <div x-show="toastOpen" x-transition class="fixed right-6 top-5 z-50">
        <div class="rounded-xl border border-teal-200 bg-white px-4 py-3 text-sm font-medium text-slate-800 shadow-lg">
            <span class="text-teal-700">Succes:</span>
            <span x-text="toastMessage"></span>
        </div>
    </div>

    @if($errors->has('delete_student'))
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-900">
            {{ $errors->first('delete_student') }}
        </div>
    @endif

    <x-ui.card class="p-4">
        <div class="space-y-4">
            <div class="flex flex-wrap gap-2">
                @foreach([
                    'active' => 'Actifs',
                    'archived' => 'Archives',
                    'all' => 'Tous',
                ] as $statusKey => $statusLabel)
                    <a href="{{ route('admin.students.index', array_filter(['classroom' => $classroomId, 'q' => $q, 'status' => $statusKey === 'active' ? null : $statusKey])) }}"
                       class="rounded-xl border px-3 py-1.5 text-sm font-semibold transition {{ $status === $statusKey ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}">
                        {{ $statusLabel }}
                    </a>
                @endforeach
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.students.index', array_filter(['q' => $q, 'status' => $status === 'active' ? null : $status])) }}"
                   class="rounded-xl border px-3 py-1.5 text-sm font-semibold transition {{ !$classroomId ? 'border-blue-500 bg-blue-600 text-white' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}">
                    Toutes les classes
                </a>
                @foreach($classrooms as $c)
                    <a href="{{ route('admin.students.index', array_filter(['classroom' => $c->id, 'q' => $q, 'status' => $status === 'active' ? null : $status])) }}"
                       class="rounded-xl border px-3 py-1.5 text-sm font-semibold transition {{ (string)$classroomId === (string)$c->id ? 'border-blue-500 bg-blue-600 text-white' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}">
                        {{ $c->name }}
                    </a>
                @endforeach
            </div>
        </div>
    </x-ui.card>

    <x-ui.card padding="p-0" class="overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
            <p class="text-sm font-semibold text-slate-800">Liste des eleves</p>
            <span class="app-badge app-badge-info">{{ $students->total() }} resultats</span>
        </div>

        <div class="overflow-x-auto">
            <table class="app-table">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Classe</th>
                        <th>Parent</th>
                        <th>Statut</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                        <tr class="transition hover:bg-blue-50/60">
                            <td>
                                <p class="font-semibold text-slate-900">{{ $student->full_name }}</p>
                                @if($student->birth_date)
                                    <p class="text-xs text-slate-500">Ne le {{ \Carbon\Carbon::parse($student->birth_date)->format('d/m/Y') }}</p>
                                @endif
                            </td>
                            <td>{{ $student->classroom?->name ?? '-' }}</td>
                            <td>
                                <p class="font-medium text-slate-800">{{ $student->parentUser?->name ?? '-' }}</p>
                                <p class="text-xs text-slate-500">{{ $student->parentUser?->phone ?? $student->parentUser?->email ?? '-' }}</p>
                            </td>
                            <td>
                                <div class="flex flex-wrap gap-1">
                                    @if($student->is_archived)
                                        <span class="app-badge app-badge-warning">Archive</span>
                                    @else
                                        <span class="app-badge app-badge-success">Actif</span>
                                    @endif

                                    @if($student->transportAssignment?->is_active)
                                        <span class="app-badge app-badge-success">Transport actif</span>
                                    @endif

                                    @if($student->feePlan?->insurance_paid)
                                        <span class="app-badge app-badge-info">Assurance payee</span>
                                    @endif

                                    @if(!($student->transportAssignment?->is_active) && !($student->feePlan?->insurance_paid))
                                        <span class="app-badge app-badge-warning">A verifier</span>
                                    @endif
                                </div>
                            </td>
                            <td class="text-right">
                                <div class="flex flex-wrap justify-end gap-2">
                                    @if(Route::has('admin.students.show'))
                                        <x-ui.button :href="route('admin.students.show', $student)" size="sm" variant="ghost">Voir</x-ui.button>
                                    @endif
                                    <x-ui.button :href="route('admin.students.edit', $student)" size="sm" variant="secondary">Modifier</x-ui.button>
                                    @if(Route::has('admin.students.fees.edit'))
                                        <x-ui.button :href="route('admin.students.fees.edit', $student)" size="sm" variant="ghost">Paiements</x-ui.button>
                                    @endif
                                    @if(Route::has('admin.courses.index'))
                                        <x-ui.button :href="route('admin.courses.index', ['student_id' => $student->id])" size="sm" variant="ghost">Cours</x-ui.button>
                                    @endif
                                    @if($student->is_archived)
                                        <form method="POST" action="{{ route('admin.students.reactivate', $student) }}">
                                            @csrf
                                            <x-ui.button type="submit" size="sm" variant="secondary">Reactiver</x-ui.button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.students.archive', $student) }}" onsubmit="return confirm('Archiver cet eleve ? Son historique sera conserve.')">
                                            @csrf
                                            <x-ui.button type="submit" size="sm" variant="ghost">Archiver</x-ui.button>
                                        </form>
                                    @endif
                                    <button
                                        type="button"
                                        class="inline-flex items-center justify-center rounded-xl border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-100"
                                        x-on:click.prevent="openConfirm(@js(route('admin.students.destroy', $student)), @js($student->full_name))"
                                    >
                                        Supprimer
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-14 text-center">
                                <div class="mx-auto max-w-sm">
                                    <p class="text-base font-semibold text-slate-800">Aucun eleve trouve</p>
                                    <p class="mt-1 text-sm text-slate-500">Ajustez la recherche ou ajoutez un nouvel eleve.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
        {{ $students->links() }}
    </div>

    <div x-cloak x-show="deleteAction" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4">
        <div @click.outside="deleteAction = ''" class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-xl">
            <h3 class="text-lg font-semibold text-slate-900">Confirmer la suppression</h3>
            <p class="mt-2 text-sm text-slate-600">
                Vous allez supprimer l'eleve
                <span class="font-semibold text-slate-900" x-text="deleteName"></span>.
                Cette action est irreversible et sera bloquee si l'eleve possede deja des notes, presences, paiements, transport ou un compte eleve.
            </p>
            <div class="mt-6 flex justify-end gap-2">
                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                    x-on:click="deleteAction = ''"
                >
                    Annuler
                </button>
                <form x-bind:action="deleteAction" method="POST">
                    @csrf
                    @method('DELETE')
                    <x-ui.button type="submit" variant="danger">Supprimer</x-ui.button>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const input = document.getElementById('studentsSearch');
            const box = document.getElementById('studentsSuggestBox');
            if (!input || !box) return;

            let timer = null;
            const hideBox = () => { box.classList.add('hidden'); box.innerHTML = ''; };
            const showBox = () => box.classList.remove('hidden');

            input.addEventListener('input', () => {
                const val = input.value.trim();
                clearTimeout(timer);
                if (val.length < 2) { hideBox(); return; }

                timer = setTimeout(async () => {
                    try {
                        const url = new URL("{{ route('admin.students.suggest') }}", window.location.origin);
                        url.searchParams.set('q', val);
                        const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
                        const items = await res.json();

                        if (!Array.isArray(items) || !items.length) { hideBox(); return; }

                        box.innerHTML = items.map((it) => `
                            <button type="button" class="w-full border-b border-slate-100 px-4 py-3 text-left last:border-b-0 hover:bg-slate-50">
                                <div class="text-sm font-semibold text-slate-900">${it.label}</div>
                                <div class="text-xs text-slate-500">${it.meta ?? ''}</div>
                            </button>
                        `).join('');

                        Array.from(box.querySelectorAll('button')).forEach((btn, idx) => {
                            btn.addEventListener('click', () => {
                                input.value = items[idx].label;
                                hideBox();
                                input.closest('form').submit();
                            });
                        });

                        showBox();
                    } catch (e) {
                        hideBox();
                    }
                }, 200);
            });

            document.addEventListener('click', (e) => {
                if (!box.contains(e.target) && e.target !== input) hideBox();
            });
        })();
    </script>
</div>
