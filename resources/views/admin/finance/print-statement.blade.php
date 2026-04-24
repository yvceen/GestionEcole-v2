<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>MyEdu</title>
  @vite(['resources/css/app.css'])
  <style>
    @media print {
      .no-print { display:none !important; }
      body { background:#fff !important; }
      .print-shadow-none { box-shadow:none !important; }
      .print-border { border:1px solid #e2e8f0 !important; }
      .print-p-0 { padding:0 !important; }
    }
  </style>
</head>

<body class="bg-slate-50 text-slate-900">
  @php
    $month = $month ?? now()->format('Y-m');
    $title = $title ?? 'Releve';
    $total = (float) ($total ?? 0);
    $count = isset($payments) ? $payments->count() : 0;
    $type = $type ?? request('type', '');
    $id = $id ?? (int) request('id', 0);
    $school = app()->bound('currentSchool')
        ? app('currentSchool')
        : (app()->bound('current_school') ? app('current_school') : null);
    $schoolName = $school?->name ?? config('app.name', 'MyEdu');
    $logoPath = $school?->logo_path ?? null;
    $defaultLogoUrl = asset('images/edulogo.jpg') . '?v=3';
    $schoolLogoUrl = null;

    if (is_string($logoPath) && $logoPath !== '') {
        $trimmedLogoPath = ltrim($logoPath, '/');
        if (\Illuminate\Support\Str::startsWith($logoPath, ['http://', 'https://'])) {
            $schoolLogoUrl = $logoPath;
        } elseif (\Illuminate\Support\Facades\Storage::disk('public')->exists($trimmedLogoPath)) {
            $schoolLogoUrl = asset('storage/' . $trimmedLogoPath);
        }
    }
  @endphp

  <div class="mx-auto max-w-4xl p-8 print-p-0">
    <div class="flex items-start justify-between gap-6">
      <div class="flex items-start gap-4">
        <div class="grid h-14 w-14 place-items-center overflow-hidden rounded-2xl border border-slate-200 bg-white">
          <img
            src="{{ $schoolLogoUrl ?: $defaultLogoUrl }}"
            alt="Logo {{ $schoolName }}"
            class="h-full w-full object-contain"
            onerror="this.onerror=null;this.src='{{ $defaultLogoUrl }}';"
          >
        </div>
        <div>
          <div class="text-2xl font-semibold">{{ $schoolName }}</div>
          <div class="text-sm text-slate-500">Releve - {{ $month }}</div>
          <div class="mt-2 text-sm text-slate-700">
            <span class="font-semibold">{{ $title }}</span>
            @if($type && $id)
              <span class="text-slate-500">({{ $type }} #{{ $id }})</span>
            @endif
          </div>
        </div>
      </div>

      <div class="no-print flex items-center gap-3">
        <button onclick="window.print()" class="app-button-primary">
          Imprimer
        </button>
      </div>
    </div>

    <div class="mt-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm print-border print-shadow-none">
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
          <div class="text-xs text-slate-500">Total</div>
          <div class="mt-1 text-lg font-semibold text-slate-900">{{ number_format($total, 2) }} MAD</div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
          <div class="text-xs text-slate-500">Operations</div>
          <div class="mt-1 text-lg font-semibold text-slate-900">{{ $count }}</div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
          <div class="text-xs text-slate-500">Periode</div>
          <div class="mt-1 text-lg font-semibold text-slate-900">{{ $month }}</div>
        </div>
      </div>

      <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200">
        <table class="app-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Periode</th>
              <th>Parent</th>
              <th>Eleve</th>
              <th>Methode</th>
              <th class="text-right">Montant</th>
              <th>Recu</th>
            </tr>
          </thead>

          <tbody>
            @forelse($payments as $payment)
              <tr>
                <td>{{ $payment->paid_at?->format('Y-m-d H:i') ?? '-' }}</td>
                <td>{{ $payment->period_month?->format('Y-m') ?? '-' }}</td>
                <td>{{ $payment->receipt?->parent?->name ?? $payment->student?->parentUser?->name ?? '-' }}</td>
                <td class="font-semibold text-slate-900">{{ $payment->student?->full_name ?? '-' }}</td>
                <td class="uppercase text-xs font-semibold text-slate-600">{{ $payment->method ?? '-' }}</td>
                <td class="text-right font-semibold text-slate-900">{{ number_format((float) ($payment->amount ?? 0), 2) }} MAD</td>
                <td>{{ $payment->receipt?->receipt_number ?? '-' }}</td>
              </tr>

              @if(!empty($payment->note))
                <tr>
                  <td colspan="7" class="px-5 py-3 text-xs text-slate-500">
                    Note : <span class="font-semibold text-slate-700">{{ $payment->note }}</span>
                  </td>
                </tr>
              @endif
            @empty
              <tr>
                <td colspan="7" class="px-5 py-8 text-sm text-slate-500">Aucune operation.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="mt-6 flex items-center justify-between text-xs text-slate-500">
        <div>Genere le : {{ now()->format('Y-m-d H:i') }}</div>
        <div>Powered by MyEdu</div>
      </div>
    </div>
  </div>
</body>
</html>
