@extends('layouts.receipt')

@section('title', 'Recu evenement ' . ($payment->receipt_number ?? ''))
@section('heading', 'Recu evenement')
@section('receipt_variant', 'a4')

@section('actions')
    <a
        href="{{ route($routePrefix . '.show', $payment->event) }}"
        class="inline-flex items-center rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-slate-400 hover:bg-slate-50"
    >
        Retour evenement
    </a>
    <button
        type="button"
        onclick="window.print()"
        class="inline-flex items-center rounded-full border border-sky-700 bg-sky-700 px-4 py-2 text-sm font-medium text-white transition hover:bg-sky-800"
    >
        Imprimer
    </button>
@endsection

@section('content')
    @php
        $school = $payment->event?->school;
        $methodLabels = [
            'cash' => 'Especes',
            'transfer' => 'Virement',
            'card' => 'Carte',
            'check' => 'Cheque',
        ];
    @endphp

    <article class="receipt-document overflow-hidden rounded-[28px] border border-slate-200">
        <div class="border-b border-slate-200 bg-gradient-to-br from-slate-950 via-blue-950 to-teal-800 px-8 py-8 text-white">
            <div class="flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.22em] text-sky-100">Recu de paiement</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight">{{ $payment->receipt_number }}</h2>
                    <p class="mt-2 text-sm text-slate-200">{{ $school?->name ?? config('app.name') }}</p>
                </div>
                <div class="rounded-2xl border border-white/15 bg-white/10 px-5 py-4 text-right">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-sky-100">Montant</p>
                    <p class="mt-2 text-3xl font-bold">{{ number_format((float) $payment->amount, 2) }} MAD</p>
                </div>
            </div>
        </div>

        <div class="grid gap-6 px-8 py-8 lg:grid-cols-2">
            <section class="rounded-3xl border border-slate-200 bg-slate-50 px-5 py-5">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Evenement</p>
                <h3 class="mt-3 text-xl font-semibold text-slate-950">{{ $payment->event?->title ?? '-' }}</h3>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Date evenement</dt>
                        <dd class="font-semibold text-slate-900">{{ optional($payment->event?->event_date)->format('d/m/Y') ?: '-' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Date paiement</dt>
                        <dd class="font-semibold text-slate-900">{{ $payment->paid_at?->format('d/m/Y H:i') ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Methode</dt>
                        <dd class="font-semibold text-slate-900">{{ $methodLabels[$payment->method] ?? strtoupper((string) $payment->method) }}</dd>
                    </div>
                </dl>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white px-5 py-5">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Beneficiaire</p>
                <h3 class="mt-3 text-xl font-semibold text-slate-950">{{ $payment->student?->full_name ?? '-' }}</h3>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Classe</dt>
                        <dd class="font-semibold text-slate-900">{{ $payment->student?->classroom?->name ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Parent</dt>
                        <dd class="font-semibold text-slate-900">{{ $payment->parent?->name ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Recu par</dt>
                        <dd class="font-semibold text-slate-900">{{ $payment->receivedBy?->name ?? '-' }}</dd>
                    </div>
                </dl>
            </section>
        </div>

        @if($payment->note)
            <div class="px-8 pb-8">
                <div class="rounded-3xl border border-amber-200 bg-amber-50 px-5 py-4">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-amber-700">Note</p>
                    <p class="mt-2 text-sm leading-6 text-amber-900">{{ $payment->note }}</p>
                </div>
            </div>
        @endif

        <div class="border-t border-slate-200 px-8 py-6">
            <div class="grid gap-6 sm:grid-cols-2">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Signature administration</p>
                    <div class="mt-10 border-t border-slate-300"></div>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Signature parent</p>
                    <div class="mt-10 border-t border-slate-300"></div>
                </div>
            </div>
        </div>
    </article>
@endsection
