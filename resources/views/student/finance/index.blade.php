<x-student-layout title="Mon suivi financier" subtitle="Consultez vos paiements, imprimez vos recus et gardez une vue claire de votre dossier financier.">
    <section class="grid gap-4 md:grid-cols-3">
        <article class="student-kpi">
            <p class="student-kpi-label">Total regle</p>
            <p class="student-kpi-value">{{ number_format($paymentsTotal, 2) }} MAD</p>
            <p class="student-kpi-copy">Montant cumule de vos paiements.</p>
        </article>
        <article class="student-kpi">
            <p class="student-kpi-label">Operations</p>
            <p class="student-kpi-value">{{ $paymentsCount }}</p>
            <p class="student-kpi-copy">Lignes de paiement enregistrees.</p>
        </article>
        <article class="student-kpi">
            <p class="student-kpi-label">Dernier paiement</p>
            <p class="mt-3 text-xl font-semibold tracking-tight text-slate-950">{{ $lastPayment?->paid_at?->format('d/m/Y') ?? '-' }}</p>
            <p class="mt-2 text-sm text-slate-600">{{ $student->classroom?->name ?? '-' }}</p>
        </article>
    </section>

    @unless($pdfAvailable)
        <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            Le telechargement PDF est pret dans le code, mais le package Dompdf n'est pas installe sur cet environnement. L'impression HTML reste disponible.
        </div>
    @endunless

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
                        <a href="{{ route('student.finance.receipts.show', $receipt) }}" data-loading-label="Ouverture du recu..." class="app-button-secondary rounded-full px-4 py-2 text-sm font-semibold">
                            Voir le recu
                        </a>
                        <a href="{{ route('student.finance.receipts.export', $receipt) }}" target="_blank" class="app-button-primary rounded-full px-4 py-2 text-sm font-semibold">
                            Imprimer
                        </a>
                        @if($pdfAvailable)
                            <a href="{{ route('student.finance.receipts.pdf', $receipt) }}" class="app-button-outline rounded-full px-4 py-2 text-sm font-semibold">
                                Telecharger PDF
                            </a>
                        @else
                            <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-500">
                                PDF indisponible
                            </span>
                        @endif
                    </div>
                </div>

                <div class="mt-5 grid gap-4 lg:grid-cols-[minmax(0,1fr)_220px]">
                    <div class="overflow-hidden rounded-2xl border border-slate-200">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-left text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">
                                <tr>
                                    <th class="px-4 py-3">Mois</th>
                                    <th class="px-4 py-3">Classe</th>
                                    <th class="px-4 py-3 text-right">Montant</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @foreach($receipt->payments as $payment)
                                    <tr>
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
                        <p class="mt-2 text-sm text-slate-600">{{ $receipt->payments->count() }} ligne(s) pour votre dossier.</p>
                    </div>
                </div>
            </article>
        @empty
            <div class="student-empty">
                Aucun paiement enregistre pour le moment.
            </div>
        @endforelse
    </section>

    <div class="mt-5">
        {{ $receipts->links() }}
    </div>
</x-student-layout>
