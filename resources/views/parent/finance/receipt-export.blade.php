@extends('layouts.receipt')

@section('title', 'Recu ' . ($receipt->receipt_number ?? ''))
@section('heading', 'Export du recu')
@section('receipt_variant', 'a4')

@section('actions')
    <a
        href="{{ route('parent.finance.receipts.show', $receipt) }}"
        class="app-button-secondary rounded-full px-4 py-2 text-sm font-medium"
    >
        Retour au recu
    </a>
    <button
        type="button"
        onclick="window.print()"
        class="app-button-primary rounded-full px-4 py-2 text-sm font-medium"
    >
        Imprimer
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
