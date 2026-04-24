@php
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
@endphp
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Liste des pieces d inscription</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #0f172a; margin: 0; padding: 26px; font-size: 12px; }
        .sheet { border: 1px solid #dbe3ee; border-radius: 20px; overflow: hidden; }
        .header { padding: 24px 26px; border-bottom: 1px solid #dbe3ee; background: #f3f9fd; }
        .logo { width: 56px; height: 56px; border-radius: 14px; border: 1px solid #dbe3ee; background: white; padding: 6px; }
        .title { font-size: 24px; font-weight: 700; margin: 8px 0 0; }
        .muted { color: #64748b; }
        .section { padding: 18px 26px; }
        .section-title { font-size: 16px; font-weight: 700; margin: 0 0 4px; }
        .item { border: 1px solid #e2e8f0; border-radius: 16px; padding: 14px 16px; margin-top: 10px; }
        .checkbox { display: inline-block; width: 13px; height: 13px; border: 2px solid #0ea5e9; border-radius: 4px; margin-right: 10px; vertical-align: middle; }
        .optional { border-color: #94a3b8; }
        .badge { display: inline-block; font-size: 10px; padding: 4px 8px; border-radius: 999px; margin-left: 8px; }
        .required { background: #e0f2fe; color: #0369a1; }
        .optional-badge { background: #f1f5f9; color: #475569; }
        .footer { border-top: 1px solid #dbe3ee; padding: 16px 26px; background: #f8fafc; }
    </style>
</head>
<body>
    <div class="sheet">
        <div class="header">
            <table width="100%">
                <tr>
                    <td width="72%" valign="top">
                        @if($schoolLogoUrl)
                            <img src="{{ $schoolLogoUrl }}" class="logo" alt="Logo">
                        @endif
                        <div class="muted" style="margin-top: 10px;">MyEdu | {{ $context['portal'] }}</div>
                        <div class="title">{{ $schoolName }}</div>
                        <div class="muted" style="margin-top: 6px;">Liste des pieces et fournitures d inscription</div>
                    </td>
                    <td width="28%" valign="top" align="right">
                        <div class="muted">Format</div>
                        <div style="font-weight: 700; margin-top: 6px;">A4 imprimable</div>
                    </td>
                </tr>
            </table>
        </div>

        @foreach($groupedItems as $category => $items)
            <div class="section">
                <p class="section-title">{{ $category }}</p>
                <p class="muted" style="margin: 0 0 10px;">{{ $items->count() }} element(s)</p>
                @foreach($items as $item)
                    <div class="item">
                        <span class="checkbox {{ $item->is_required ? '' : 'optional' }}"></span>
                        <span style="font-weight: 700;">{{ $item->label }}</span>
                        <span class="badge {{ $item->is_required ? 'required' : 'optional-badge' }}">
                            {{ $item->is_required ? 'Obligatoire' : 'Optionnel' }}
                        </span>
                        @if(filled($item->notes))
                            <div class="muted" style="margin-top: 8px; line-height: 1.5;">{{ $item->notes }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endforeach

        <div class="footer">
            <span class="muted">Document genere depuis MyEdu pour remise aux familles.</span>
        </div>
    </div>
</body>
</html>
