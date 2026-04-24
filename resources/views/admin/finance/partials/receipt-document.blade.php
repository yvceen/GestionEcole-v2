@php
    $payments = $receipt->payments ?? collect();
    $school = $receipt->school
        ?? (app()->bound('currentSchool') ? app('currentSchool') : (app()->bound('current_school') ? app('current_school') : null));
    $schoolName = $school?->name ?? config('app.name', 'MyEdu');
    $logoPath = is_string($school?->logo_path ?? null) ? ltrim((string) $school->logo_path, '/') : '';
    $schoolLogoUrl = null;

    if ($logoPath !== '') {
        if (\Illuminate\Support\Str::startsWith($logoPath, ['http://', 'https://'])) {
            $schoolLogoUrl = $logoPath;
        } elseif (\Illuminate\Support\Facades\Storage::disk('public')->exists($logoPath)) {
            $schoolLogoUrl = asset('storage/' . $logoPath);
        }
    }

    $total = (float) ($receipt->total_amount ?? $payments->sum('amount'));
    $parentName = $receipt->parent?->name ?? ($payments->first()?->student?->parentUser?->name ?? '-');
    $parentEmail = $receipt->parent?->email ?? ($payments->first()?->student?->parentUser?->email ?? null);
    $parentPhone = $receipt->parent?->phone ?? ($payments->first()?->student?->parentUser?->phone ?? null);
    $issuedAt = optional($receipt->issued_at)->format('d/m/Y H:i') ?: '-';
    $paymentMethod = strtoupper((string) ($receipt->method ?? ($payments->first()?->method ?? '-')));
    $receivedBy = $receipt->receivedBy?->name ?? ('Admin #' . ($receipt->received_by_admin_user_id ?? '-'));
@endphp

<article class="receipt-document overflow-hidden rounded-[28px] border border-slate-200">
    <header class="border-b border-slate-200 bg-[linear-gradient(135deg,rgba(14,116,144,0.08),rgba(255,255,255,0.98))] px-6 py-6 sm:px-8">
        <div class="flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex min-w-0 items-start gap-4">
                @if($schoolLogoUrl)
                    <div class="grid h-16 w-16 shrink-0 place-items-center overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <img
                            src="{{ $schoolLogoUrl }}"
                            alt="Logo {{ $schoolName }}"
                            class="h-full w-full object-contain"
                        >
                    </div>
                @endif

                <div class="min-w-0">
                    <p class="text-[0.72rem] font-semibold uppercase tracking-[0.22em] text-slate-500">Recu de paiement</p>
                    <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">{{ $schoolName }}</h1>
                    <p class="mt-2 text-sm text-slate-500">Document professionnel prepare pour archivage et impression.</p>
                </div>
            </div>

            <div class="min-w-[220px] rounded-2xl border border-slate-200 bg-white px-4 py-4 text-right shadow-sm">
                <p class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Numero de recu</p>
                <p class="mt-2 text-base font-semibold text-slate-950">{{ $receipt->receipt_number }}</p>
                <div class="mt-4 border-t border-slate-100 pt-3 text-sm text-slate-500">
                    <p>Date : <span class="font-medium text-slate-900">{{ $issuedAt }}</span></p>
                </div>
            </div>
        </div>
    </header>

    <section class="grid gap-4 px-6 py-6 sm:grid-cols-[1.05fr_0.95fr] sm:px-8">
        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4">
            <p class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Parent</p>
            <p class="mt-3 text-lg font-semibold text-slate-950">{{ $parentName }}</p>

            @if($parentEmail || $parentPhone)
                <div class="mt-3 space-y-1 text-sm text-slate-600">
                    @if($parentEmail)
                        <p>{{ $parentEmail }}</p>
                    @endif
                    @if($parentPhone)
                        <p>{{ $parentPhone }}</p>
                    @endif
                </div>
            @else
                <p class="mt-3 text-sm text-slate-500">Aucun contact parent renseigne.</p>
            @endif
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Total regle</p>
                    <p class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">{{ number_format($total, 2) }} MAD</p>
                </div>

                <div class="text-right">
                    <p class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Methode</p>
                    <p class="mt-3 text-sm font-semibold text-slate-950">{{ $paymentMethod }}</p>
                </div>
            </div>

            <div class="mt-5 grid gap-2 border-t border-slate-100 pt-4 text-sm text-slate-600">
                <div class="flex items-center justify-between gap-3">
                    <span>Date de paiement</span>
                    <span class="font-medium text-slate-950">{{ $issuedAt }}</span>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <span>Encaisse par</span>
                    <span class="font-medium text-slate-950">{{ $receivedBy }}</span>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <span>Nombre de lignes</span>
                    <span class="font-medium text-slate-950">{{ $payments->count() }}</span>
                </div>
            </div>
        </div>
    </section>

    <section class="px-6 pb-6 sm:px-8">
        <div class="overflow-hidden rounded-2xl border border-slate-200">
            <table class="receipt-table text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-left text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">
                        <th class="px-4 py-3">Eleve</th>
                        <th class="px-4 py-3">Classe</th>
                        <th class="px-4 py-3">Mois</th>
                        <th class="px-4 py-3 text-right">Montant</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($payments as $payment)
                        <tr>
                            <td class="px-4 py-3 font-semibold text-slate-950">{{ $payment->student?->full_name ?? '-' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $payment->student?->classroom?->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ optional($payment->period_month)->format('Y-m') ?? '-' }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-slate-950">{{ number_format((float) $payment->amount, 2) }} MAD</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-sm text-slate-500">Aucun paiement trouve pour ce recu.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <footer class="border-t border-slate-200 bg-slate-50/70 px-6 py-5 sm:px-8">
        @if(!empty($receipt->note))
            <div class="mb-4 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700">
                <span class="font-semibold text-slate-950">Note :</span> {{ $receipt->note }}
            </div>
        @endif

        <div class="flex flex-col gap-2 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between">
            <span>Merci pour votre reglement.</span>
            <span class="font-semibold tracking-[0.16em] text-slate-400">Powered by MyEdu</span>
        </div>
    </footer>
</article>
