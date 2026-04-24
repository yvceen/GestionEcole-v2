@extends('layouts.receipt')

@section('title', 'Recu ' . ($receipt->receipt_number ?? ''))
@section('heading', 'Recu de paiement')
@section('receipt_variant', 'a4')

@section('actions')
    <a
        href="{{ route('admin.finance.index') }}"
        class="inline-flex items-center rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-slate-400 hover:bg-slate-50"
    >
        Retour a la finance
    </a>
    <a
        href="{{ route('admin.finance.receipts.export', $receipt) }}"
        target="_blank"
        class="inline-flex items-center rounded-full border border-slate-200 bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800"
    >
        Export PDF
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
    @include('admin.finance.partials.receipt-document', ['receipt' => $receipt])
@endsection
