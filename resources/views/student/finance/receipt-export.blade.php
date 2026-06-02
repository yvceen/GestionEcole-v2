@extends('layouts.receipt')

@section('title', 'Reçu ' . ($receipt->receipt_number ?? ''))
@section('heading', 'Export du reçu')
@section('receipt_variant', 'a4')

@section('actions')
    <a
        href="{{ route('student.finance.receipts.show', $receipt) }}"
        class="app-button-secondary rounded-full px-4 py-2 text-sm font-medium"
    >
        Retour au reçu
    </a>
    @if($pdfAvailable ?? false)
        <a
            href="{{ route('student.finance.receipts.pdf', $receipt) }}"
            class="app-button-outline rounded-full px-4 py-2 text-sm font-medium"
        >
            Telecharger PDF
        </a>
    @endif
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
