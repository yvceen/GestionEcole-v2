<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'MyEdu'))</title>

    <x-school-favicons />

    @vite(['resources/css/app.css'])

    @php($receiptVariant = trim($__env->yieldContent('receipt_variant')) ?: 'a4')

    <style>
        @page {
            size: A4;
            margin: 12mm;
        }

        body {
            background: #f8fafc;
        }

        .receipt-shell {
            min-height: 100vh;
            padding: 2rem 1rem;
        }

        .receipt-sheet {
            margin: 0 auto;
            width: 100%;
        }

        .receipt-sheet[data-variant="a4"] {
            max-width: 920px;
        }

        .receipt-sheet[data-variant="compact"] {
            max-width: 430px;
        }

        .receipt-document {
            background: #ffffff;
            box-shadow: 0 24px 65px -38px rgba(15, 23, 42, 0.35);
        }

        .receipt-table {
            width: 100%;
            border-collapse: collapse;
        }

        .receipt-table th,
        .receipt-table td {
            vertical-align: top;
        }

        @media print {
            body {
                background: white !important;
            }

            .no-print {
                display: none !important;
            }

            .receipt-shell {
                padding: 0 !important;
            }

            .receipt-sheet {
                max-width: none !important;
            }

            .receipt-document {
                box-shadow: none !important;
                border-color: #cbd5e1 !important;
                border-radius: 0 !important;
            }
        }
    </style>
</head>
<body class="text-slate-900 antialiased">
    <div class="receipt-shell">
        <main class="receipt-sheet" data-variant="{{ $receiptVariant }}">
            @hasSection('actions')
                <div class="no-print mb-5 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Finance</p>
                        <h1 class="mt-1 text-2xl font-semibold text-slate-950">@yield('heading', 'Recu')</h1>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        @yield('actions')
                    </div>
                </div>
            @endif

            @if(session('success'))
                <div class="no-print mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>
