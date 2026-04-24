@extends('layouts.receipt')

@section('title', 'Recu ' . ($receipt->receipt_number ?? ''))
@section('heading', 'Recu parent')
@section('receipt_variant', 'a4')

@section('actions')
    <a
        href="{{ route('parent.finance.index') }}"
        class="app-button-secondary rounded-full px-4 py-2 text-sm font-medium"
    >
        Retour a la finance
    </a>
    <a
        href="{{ route('parent.finance.receipts.export', $receipt) }}"
        target="_blank"
        class="app-button-primary rounded-full px-4 py-2 text-sm font-medium"
    >
        Imprimer
    </a>
@endsection

@section('content')
    @include('admin.finance.partials.receipt-document', ['receipt' => $receipt])
@endsection
