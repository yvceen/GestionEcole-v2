<x-parent-layout title="Finance" subtitle="Suivez l historique des paiements de vos enfants, consultez les recus et gardez une vue claire sur votre dossier.">
    <section class="student-panel">
        <form method="GET" data-loading-label="Filtrage de la finance..." class="grid gap-3 md:grid-cols-[minmax(0,1fr)_180px]">
            <select name="child_id" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                <option value="">Tous mes enfants</option>
                @foreach($children as $child)
                    <option value="{{ $child->id }}" @selected((string) $childId === (string) $child->id)>
                        {{ $child->full_name }} - {{ $child->classroom?->name ?? '-' }}
                    </option>
                @endforeach
            </select>
            <button class="app-button-primary rounded-2xl px-4 py-3">Filtrer</button>
        </form>
    </section>

    <section class="mt-6 grid gap-4 md:grid-cols-5">
        <article class="student-kpi">
            <p class="student-kpi-label">Total regle</p>
            <p class="student-kpi-value">{{ number_format($paymentsTotal, 2) }} MAD</p>
            <p class="student-kpi-copy">Montant cumule sur votre selection.</p>
        </article>
        <article class="student-kpi">
            <p class="student-kpi-label">Operations</p>
            <p class="student-kpi-value">{{ $paymentsCount }}</p>
            <p class="student-kpi-copy">Lignes de paiement enregistrees.</p>
        </article>
        <article class="student-kpi">
            <p class="student-kpi-label">Dernier paiement</p>
            <p class="mt-3 text-xl font-semibold tracking-tight text-slate-950">{{ $lastPayment?->paid_at?->format('d/m/Y') ?? '-' }}</p>
            <p class="mt-2 text-sm text-slate-600">{{ $lastPayment?->student?->full_name ?? '-' }}</p>
        </article>
        <article class="student-kpi">
            <p class="student-kpi-label">Mois impayes</p>
            <p class="student-kpi-value text-amber-700">{{ (int) ($arrears['total_unpaid_months'] ?? 0) }}</p>
            <p class="student-kpi-copy">{{ number_format((float) ($arrears['total_due'] ?? 0), 2) }} MAD attendus.</p>
        </article>
        <article class="student-kpi">
            <p class="student-kpi-label">Retard paiement</p>
            <p class="student-kpi-value text-rose-700">{{ (int) ($arrears['total_overdue_months'] ?? 0) }}</p>
            <p class="student-kpi-copy">{{ number_format((float) ($arrears['total_overdue'] ?? 0), 2) }} MAD en retard.</p>
        </article>
    </section>

    <section class="student-panel mt-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="student-panel-title">Mois impayes et retards</p>
                <p class="student-panel-copy">Calcul base sur le plan de frais mensuel de chaque enfant et les mois deja regles.</p>
            </div>
            <span class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">
                Retard = mois avant le mois courant
            </span>
        </div>

        <div class="mt-5 grid gap-4 lg:grid-cols-2">
            @forelse(($arrears['by_child'] ?? collect()) as $item)
                <article class="rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="font-semibold text-slate-950">{{ $item['student']->full_name }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $item['student']->classroom?->name ?? '-' }}</p>
                        </div>
                        <div class="text-left sm:text-right">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Mensuel</p>
                            <p class="mt-1 font-semibold text-slate-950">{{ number_format((float) $item['monthly_due'], 2) }} MAD</p>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-2xl border border-amber-200 bg-white px-3 py-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-amber-700">Impayes</p>
                            <p class="mt-2 text-xl font-semibold text-slate-950">{{ $item['unpaid_count'] }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ number_format((float) $item['unpaid_total'], 2) }} MAD</p>
                        </div>
                        <div class="rounded-2xl border border-rose-200 bg-white px-3 py-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-rose-700">En retard</p>
                            <p class="mt-2 text-xl font-semibold text-slate-950">{{ $item['overdue_count'] }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ number_format((float) $item['overdue_total'], 2) }} MAD</p>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        @forelse($item['unpaid_months']->take(8) as $month)
                            <span class="rounded-full border px-3 py-1 text-xs font-semibold {{ $month['is_overdue'] ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-amber-200 bg-amber-50 text-amber-700' }}">
                                {{ $month['label'] }}
                            </span>
                        @empty
                            <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                                Aucun mois impaye
                            </span>
                        @endforelse
                        @if($item['unpaid_months']->count() > 8)
                            <span class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600">
                                +{{ $item['unpaid_months']->count() - 8 }} autre(s)
                            </span>
                        @endif
                    </div>
                </article>
            @empty
                <div class="student-empty lg:col-span-2">Aucun enfant selectionne pour le calcul financier.</div>
            @endforelse
        </div>
    </section>

    <section class="mt-6 space-y-4">
        @forelse($receipts as $receipt)
            @php($receiptTotal = (float) $receipt->payments->sum('amount'))
            <article class="student-panel">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Recu</p>
                        <h2 class="mt-2 text-xl font-semibold tracking-tight text-slate-950">{{ $receipt->receipt_number }}</h2>
                        <p class="mt-2 text-sm text-slate-500">
                            {{ $receipt->issued_at?->format('d/m/Y H:i') ?? '-' }}
                            <span class="mx-2 text-slate-300">|</span>
                            Methode {{ strtoupper((string) $receipt->method) }}
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('parent.finance.receipts.show', $receipt) }}" data-loading-label="Ouverture du recu..." class="app-button-secondary rounded-full px-4 py-2 text-sm font-semibold">
                            Voir le recu
                        </a>
                        <a href="{{ route('parent.finance.receipts.export', $receipt) }}" target="_blank" class="app-button-primary rounded-full px-4 py-2 text-sm font-semibold">
                            Imprimer
                        </a>
                    </div>
                </div>

                <div class="mt-5 grid gap-4 lg:grid-cols-[minmax(0,1fr)_220px]">
                    <div class="overflow-hidden rounded-2xl border border-slate-200">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-left text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">
                                <tr>
                                    <th class="px-4 py-3">Eleve</th>
                                    <th class="px-4 py-3">Mois</th>
                                    <th class="px-4 py-3">Classe</th>
                                    <th class="px-4 py-3 text-right">Montant</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @foreach($receipt->payments as $payment)
                                    <tr>
                                        <td class="px-4 py-3 text-slate-600">{{ $payment->student?->full_name ?? '-' }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ $payment->period_month?->format('Y-m') ?? '-' }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ $payment->student?->classroom?->name ?? '-' }}</td>
                                        <td class="px-4 py-3 text-right font-semibold text-slate-950">{{ number_format((float) $payment->amount, 2) }} MAD</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Total du recu</p>
                        <p class="mt-3 text-2xl font-semibold tracking-tight text-slate-950">{{ number_format($receiptTotal, 2) }} MAD</p>
                        <p class="mt-2 text-sm text-slate-600">{{ $receipt->payments->count() }} ligne(s) visibles.</p>
                    </div>
                </div>
            </article>
        @empty
            <div class="student-empty">Aucun paiement enregistre pour votre selection.</div>
        @endforelse
    </section>

    <div class="mt-5">
        {{ $receipts->links() }}
    </div>
</x-parent-layout>
