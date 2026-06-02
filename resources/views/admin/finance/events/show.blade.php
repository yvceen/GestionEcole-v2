@php
    $expected = (float) ($event->expected_total ?? 0);
    $paid = (float) ($event->paid_total ?? 0);
    $remainingTotal = max(0, $expected - $paid);
    $targetCount = (int) ($event->targets_count ?? $targets->count());
    $paidCount = $targets->filter(fn ($target) => (float) ($target->paid_amount ?? 0) >= (float) $target->amount_due && (float) $target->amount_due > 0)->count();
    $partialCount = $targets->filter(fn ($target) => (float) ($target->paid_amount ?? 0) > 0 && (float) ($target->paid_amount ?? 0) < (float) $target->amount_due)->count();
    $unpaidCount = $targets->filter(fn ($target) => (float) ($target->paid_amount ?? 0) <= 0 && (float) $target->amount_due > 0)->count();
    $progress = $expected > 0 ? min(100, round(($paid / $expected) * 100)) : 0;
    $methodLabels = [
        'cash' => 'Especes',
        'transfer' => 'Virement',
        'card' => 'Carte',
        'check' => 'Cheque',
    ];
@endphp

<x-dynamic-component :component="$layoutComponent" title="Événement {{ $event->title }}">
    <section class="overflow-hidden rounded-[32px] border border-sky-100 bg-[radial-gradient(circle_at_top_right,_rgba(34,197,94,0.17),_transparent_32%),radial-gradient(circle_at_bottom_left,_rgba(59,130,246,0.14),_transparent_34%),linear-gradient(135deg,#ffffff,#f8fbff_58%,#eefdf8)] px-6 py-6 text-slate-950 shadow-xl shadow-slate-200/70 md:px-8">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-3xl">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-sky-700">
                        Paiement Événement
                    </span>
                    <span class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-emerald-700">
                        {{ $event->status === 'active' ? 'Actif' : 'Cloture' }}
                    </span>
                </div>
                <h1 class="mt-4 text-3xl font-semibold tracking-tight md:text-4xl">{{ $event->title }}</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                    {{ $event->description ?: 'Suivi des parents, montants encaisses, restes a payer et reçus imprimes.' }}
                </p>
                <div class="mt-4 flex flex-wrap gap-2 text-xs font-semibold text-slate-600">
                    <span class="rounded-full border border-slate-200 bg-white px-3 py-1">Date : {{ optional($event->event_date)->format('d/m/Y') ?: '-' }}</span>
                    <span class="rounded-full border border-slate-200 bg-white px-3 py-1">Limite : {{ optional($event->due_date)->format('d/m/Y') ?: '-' }}</span>
                    <span class="rounded-full border border-slate-200 bg-white px-3 py-1">{{ number_format((float) $event->amount_per_student, 2) }} MAD / Élève</span>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <x-ui.button :href="route($routePrefix . '.edit', $event)" variant="secondary">
                    Modifier
                </x-ui.button>
                <x-ui.button :href="route($routePrefix . '.index')" variant="outline">
                    Retour
                </x-ui.button>
            </div>
        </div>
    </section>

    @if(session('success'))
        <x-ui.alert variant="success" class="mt-5">{{ session('success') }}</x-ui.alert>
    @endif

    <section class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-[26px] border border-sky-100 bg-sky-50 px-5 py-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-sky-700">Total attendu</p>
            <p class="mt-3 text-3xl font-bold tracking-tight text-slate-950">{{ number_format($expected, 2) }} MAD</p>
            <p class="mt-2 text-sm text-slate-500">{{ $targetCount }} Élève(s) concerne(s)</p>
        </div>
        <div class="rounded-[26px] border border-emerald-100 bg-emerald-50 px-5 py-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">Encaisse</p>
            <p class="mt-3 text-3xl font-bold tracking-tight text-slate-950">{{ number_format($paid, 2) }} MAD</p>
            <p class="mt-2 text-sm text-slate-500">{{ $progress }}% de progression</p>
        </div>
        <div class="rounded-[26px] border border-amber-100 bg-amber-50 px-5 py-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-amber-700">Reste</p>
            <p class="mt-3 text-3xl font-bold tracking-tight text-slate-950">{{ number_format($remainingTotal, 2) }} MAD</p>
            <p class="mt-2 text-sm text-slate-500">{{ $partialCount }} partiel(s), {{ $unpaidCount }} non payé(s)</p>
        </div>
        <div class="rounded-[26px] border border-violet-100 bg-violet-50 px-5 py-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-violet-700">Soldes</p>
            <p class="mt-3 text-3xl font-bold tracking-tight text-slate-950">{{ $paidCount }}</p>
            <p class="mt-2 text-sm text-slate-500">Paiements complets</p>
        </div>
    </section>

    <section class="mt-5 rounded-[28px] border border-slate-200 bg-white px-5 py-5 shadow-sm">
        <div class="mb-4 h-3 overflow-hidden rounded-full bg-slate-100">
            <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 via-sky-500 to-indigo-500" style="width: {{ $progress }}%"></div>
        </div>

        <form method="GET" action="{{ route($routePrefix . '.show', $event) }}" class="grid gap-3 lg:grid-cols-[minmax(0,1.4fr)_220px_220px_auto] lg:items-end">
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-800">Recherche</label>
                <input name="q" value="{{ $q }}" class="app-input" placeholder="Élève, parent, téléphone ou email">
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-800">Classe</label>
                <select name="classroom_id" class="app-input">
                    <option value="">Toutes</option>
                    @foreach($classrooms as $classroom)
                        <option value="{{ $classroom->id }}" @selected($classroomId === (int) $classroom->id)>{{ $classroom->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-800">Statut paiement</label>
                <select name="payment_status" class="app-input">
                    <option value="">Tous</option>
                    <option value="paid" @selected($paymentStatus === 'paid')>Payes</option>
                    <option value="partial" @selected($paymentStatus === 'partial')>Partiels</option>
                    <option value="unpaid" @selected($paymentStatus === 'unpaid')>Non payes</option>
                </select>
            </div>
            <div class="flex gap-2">
                <x-ui.button type="submit" variant="primary">Filtrer</x-ui.button>
                <x-ui.button :href="route($routePrefix . '.show', $event)" variant="secondary">Reset</x-ui.button>
            </div>
        </form>
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">
        <div class="rounded-[30px] border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-6 py-5">
                <h2 class="text-2xl font-semibold tracking-tight text-slate-950">Élèves et paiements</h2>
                <p class="mt-1 text-sm text-slate-500">Sélectionnez le parent ou l'Élève, encaissez, puis imprimez le reçu.</p>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse($targets as $target)
                    @php
                        $paidAmount = (float) ($target->paid_amount ?? 0);
                        $dueAmount = (float) $target->amount_due;
                        $remaining = max(0, $dueAmount - $paidAmount);
                        $isPaid = $dueAmount > 0 && $paidAmount >= $dueAmount;
                        $isPartial = $paidAmount > 0 && ! $isPaid;
                    @endphp
                    <article class="px-6 py-5">
                        <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_230px]">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    @if($isPaid)
                                        <x-ui.badge variant="success">Paye</x-ui.badge>
                                    @elseif($isPartial)
                                        <x-ui.badge variant="warning">Partiel</x-ui.badge>
                                    @else
                                        <x-ui.badge variant="danger">Non payé</x-ui.badge>
                                    @endif
                                    <span class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">{{ $target->student?->classroom?->name ?? 'Classe non définie' }}</span>
                                </div>

                                <h3 class="mt-3 text-lg font-semibold text-slate-950">{{ $target->student?->full_name ?? 'Élève supprimé' }}</h3>
                                <p class="mt-1 text-sm text-slate-500">
                                    Parent : <span class="font-semibold text-slate-700">{{ $target->student?->parentUser?->name ?? 'Non lié' }}</span>
                                    @if($target->student?->parentUser?->phone)
                                        <span class="mx-1 text-slate-300">|</span>{{ $target->student->parentUser->phone }}
                                    @endif
                                </p>

                                <div class="mt-4 grid gap-3 sm:grid-cols-3">
                                    <div class="rounded-2xl bg-slate-50 px-4 py-3">
                                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Du</p>
                                        <p class="mt-1 font-semibold text-slate-950">{{ number_format($dueAmount, 2) }} MAD</p>
                                    </div>
                                    <div class="rounded-2xl bg-emerald-50 px-4 py-3">
                                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-emerald-700">Paye</p>
                                        <p class="mt-1 font-semibold text-slate-950">{{ number_format($paidAmount, 2) }} MAD</p>
                                    </div>
                                    <div class="rounded-2xl bg-amber-50 px-4 py-3">
                                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-amber-700">Reste</p>
                                        <p class="mt-1 font-semibold text-slate-950">{{ number_format($remaining, 2) }} MAD</p>
                                    </div>
                                </div>
                            </div>

                            <form method="POST" action="{{ route($routePrefix . '.payments.store', $event) }}" class="rounded-3xl border border-slate-200 bg-slate-50 px-4 py-4">
                                @csrf
                                <input type="hidden" name="student_id" value="{{ $target->student_id }}">

                                @if($remaining <= 0)
                                    <div class="flex h-full min-h-40 flex-col items-center justify-center rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-5 text-center">
                                        <p class="text-sm font-semibold text-emerald-800">Paiement complet</p>
                                        <p class="mt-1 text-xs text-emerald-700">Aucun reste a encaisser.</p>
                                    </div>
                                @else
                                    <div class="space-y-3">
                                        <div>
                                            <label class="mb-1 block text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Montant</label>
                                            <input name="amount" type="number" min="1" step="0.01" max="{{ $remaining }}" value="{{ number_format($remaining, 2, '.', '') }}" class="app-input">
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Méthode</label>
                                            <select name="method" class="app-input">
                                                @foreach($methodLabels as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Date</label>
                                            <input name="paid_at" type="datetime-local" value="{{ now()->format('Y-m-d\TH:i') }}" class="app-input">
                                        </div>
                                        <textarea name="note" rows="2" class="app-input" placeholder="Note interne optionnelle"></textarea>
                                        <x-ui.button type="submit" variant="primary" class="w-full justify-center">Encaisser et imprimer</x-ui.button>
                                    </div>
                                @endif
                            </form>
                        </div>
                    </article>
                @empty
                    <div class="px-6 py-12 text-center text-sm text-slate-500">
                        Aucun Élève ne correspond aux filtres.
                    </div>
                @endforelse
            </div>
        </div>

        <aside class="space-y-5">
            <div class="rounded-[30px] border border-slate-200 bg-white px-5 py-5 shadow-sm">
                <h2 class="text-xl font-semibold text-slate-950">Derniers reçus</h2>
                <p class="mt-1 text-sm text-slate-500">Accès rapide aux paiements recents.</p>
                <div class="mt-4 space-y-3">
                    @forelse($payments as $payment)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-slate-950">{{ $payment->student?->full_name ?? '-' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $payment->parent?->name ?? 'Parent non lié' }} | {{ $payment->paid_at?->format('d/m/Y H:i') }}</p>
                                </div>
                                <p class="text-sm font-bold text-slate-950">{{ number_format((float) $payment->amount, 2) }}</p>
                            </div>
                            <div class="mt-3 flex items-center justify-between gap-3">
                                <span class="text-xs font-semibold text-slate-500">{{ $payment->receipt_number }}</span>
                                <a href="{{ route($routePrefix . '.payments.receipt', $payment) }}" class="text-xs font-bold text-sky-700 hover:text-sky-900">Reçu</a>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-sm text-slate-500">
                            Aucun paiement encore.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="rounded-[30px] border border-rose-100 bg-rose-50 px-5 py-5">
                <h2 class="text-lg font-semibold text-rose-950">Zone suppression</h2>
                <p class="mt-1 text-sm text-rose-700">Supprimez seulement si l'Événement est une erreur. Les paiements liés seront aussi retires.</p>
                <form method="POST" action="{{ route($routePrefix . '.destroy', $event) }}" class="mt-4" onsubmit="return confirm('Supprimer cet Événement et ses paiements ?')">
                    @csrf
                    @method('DELETE')
                    <x-ui.button type="submit" variant="danger" class="w-full justify-center">Supprimer l'Événement</x-ui.button>
                </form>
            </div>
        </aside>
    </section>
</x-dynamic-component>
