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
            $schoolLogoUrl = public_path('storage/' . $logoPath);
        }
    }

    $total = (float) ($receipt->total_amount ?? $payments->sum('amount'));
    $parentName = $receipt->parent?->name ?? ($payments->first()?->student?->parentUser?->name ?? '-');
    $issuedAt = optional($receipt->issued_at)->format('d/m/Y H:i') ?: '-';
    $paymentMethod = strtoupper((string) ($receipt->method ?? ($payments->first()?->method ?? '-')));
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>Recu {{ $receipt->receipt_number }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #0f172a;
            font-size: 12px;
            margin: 0;
            padding: 28px;
            background: #fff;
        }
        .sheet {
            border: 1px solid #dbe3ee;
            border-radius: 18px;
            overflow: hidden;
        }
        .header {
            padding: 24px 26px;
            border-bottom: 1px solid #dbe3ee;
            background: #f3f9fd;
        }
        .row {
            width: 100%;
        }
        .brand,
        .meta {
            vertical-align: top;
        }
        .brand {
            width: 65%;
        }
        .meta {
            width: 35%;
            text-align: right;
        }
        .logo {
            width: 58px;
            height: 58px;
            border: 1px solid #dbe3ee;
            border-radius: 14px;
            padding: 6px;
            background: #fff;
        }
        .muted {
            color: #64748b;
        }
        .title {
            font-size: 24px;
            font-weight: 700;
            margin: 6px 0 0;
        }
        .section {
            padding: 20px 26px;
        }
        .panel {
            border: 1px solid #dbe3ee;
            border-radius: 14px;
            padding: 14px 16px;
            background: #fff;
        }
        .panel-muted {
            background: #f8fafc;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }
        .table th,
        .table td {
            padding: 11px 12px;
            border-bottom: 1px solid #e5edf5;
        }
        .table th {
            text-align: left;
            background: #f8fafc;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
        }
        .right {
            text-align: right;
        }
        .footer {
            padding: 18px 26px 22px;
            border-top: 1px solid #dbe3ee;
            background: #f8fafc;
        }
    </style>
</head>
<body>
    <div class="sheet">
        <div class="header">
            <table class="row">
                <tr>
                    <td class="brand">
                        @if($schoolLogoUrl)
                            <img src="{{ $schoolLogoUrl }}" alt="Logo" class="logo">
                        @endif
                        <div class="muted" style="margin-top: 10px;">Recu de paiement</div>
                        <div class="title">{{ $schoolName }}</div>
                        <div class="muted" style="margin-top: 6px;">Document genere depuis l'espace eleve MyEdu.</div>
                    </td>
                    <td class="meta">
                        <div class="muted">Numero de recu</div>
                        <div style="font-size:16px;font-weight:700;margin-top:6px;">{{ $receipt->receipt_number }}</div>
                        <div class="muted" style="margin-top:14px;">Date</div>
                        <div style="font-weight:600;margin-top:4px;">{{ $issuedAt }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="section">
            <table class="row">
                <tr>
                    <td style="width:52%; padding-right: 12px; vertical-align: top;">
                        <div class="panel panel-muted">
                            <div class="muted" style="text-transform: uppercase; font-size: 11px;">Eleve</div>
                            <div style="font-size:18px; font-weight:700; margin-top:8px;">{{ $student->full_name }}</div>
                            <div class="muted" style="margin-top:8px;">Classe {{ $student->classroom?->name ?? '-' }}</div>
                            <div class="muted" style="margin-top:4px;">Parent {{ $parentName }}</div>
                        </div>
                    </td>
                    <td style="width:48%; padding-left: 12px; vertical-align: top;">
                        <div class="panel">
                            <div class="muted" style="text-transform: uppercase; font-size: 11px;">Paiement</div>
                            <div style="font-size:28px; font-weight:700; margin-top:8px;">{{ number_format($total, 2) }} MAD</div>
                            <div class="muted" style="margin-top:10px;">Methode {{ $paymentMethod }}</div>
                            <div class="muted" style="margin-top:4px;">{{ $payments->count() }} ligne(s) facturee(s)</div>
                        </div>
                    </td>
                </tr>
            </table>

            <table class="table">
                <thead>
                    <tr>
                        <th>Eleve</th>
                        <th>Classe</th>
                        <th>Mois</th>
                        <th class="right">Montant</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payments as $payment)
                        <tr>
                            <td>{{ $payment->student?->full_name ?? '-' }}</td>
                            <td>{{ $payment->student?->classroom?->name ?? '-' }}</td>
                            <td>{{ optional($payment->period_month)->format('Y-m') ?? '-' }}</td>
                            <td class="right">{{ number_format((float) $payment->amount, 2) }} MAD</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="footer">
            @if(!empty($receipt->note))
                <div style="margin-bottom: 8px;"><strong>Note :</strong> {{ $receipt->note }}</div>
            @endif
            <div class="muted">Powered by MyEdu</div>
        </div>
    </div>
</body>
</html>
