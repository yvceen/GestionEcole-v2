@extends('layouts.receipt')

@section('title', 'Recu ' . ($receipt->receipt_number ?? ''))
@section('heading', 'Export du recu')
@section('receipt_variant', 'a4')

@section('actions')
    <a
        href="{{ route('admin.finance.receipts.show', $receipt) }}"
        class="inline-flex items-center rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-slate-400 hover:bg-slate-50"
    >
        Retour au recu
    </a>
    <button
        type="button"
        onclick="window.print()"
        class="inline-flex items-center rounded-full border border-slate-900 bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800"
    >
        Imprimer / PDF
    </button>
@endsection

@section('content')
    @include('admin.finance.partials.receipt-document', ['receipt' => $receipt])
@endsection

@push('scripts')
    @if($autoPrint ?? false)
        <script>
            window.addEventListener('load', () => window.print());
        </script>
    @endif
@endpush
