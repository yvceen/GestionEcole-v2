<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $printLabel }} | {{ $school?->name ?? 'MyEdu' }}</title>
    <x-school-favicons />
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 px-4 py-8 text-slate-900 sm:px-6">
    <div class="mx-auto max-w-4xl space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3 print:hidden">
            <x-ui.button :href="$backUrl" variant="secondary">Retour</x-ui.button>
            <x-ui.button type="button" variant="primary" onclick="window.print()">
                Imprimer / Enregistrer en PDF
            </x-ui.button>
        </div>

        <section class="mx-auto max-w-xl overflow-hidden rounded-[32px] border border-slate-200 bg-white shadow-[0_24px_70px_-40px_rgba(15,23,42,0.28)]">
            <div class="relative overflow-hidden border-b border-slate-200 bg-[linear-gradient(135deg,rgba(14,116,144,0.12),rgba(255,255,255,0.98)_40%,rgba(37,99,235,0.08))] px-6 py-6">
                <div class="absolute right-0 top-0 h-32 w-32 rounded-full bg-sky-200/40 blur-3xl"></div>
                <div class="relative flex items-center gap-4">
                    <x-school-logo :size="64" class="h-16 w-16 overflow-hidden rounded-2xl shadow-sm" />
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Carte numerique</p>
                        <h1 class="mt-2 text-xl font-semibold text-slate-950">{{ $school?->name ?? 'MyEdu' }}</h1>
                        <p class="mt-1 text-sm text-slate-600">{{ $roleLabel }}</p>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 px-6 py-6 sm:grid-cols-[minmax(0,1fr)_220px]">
                <div class="space-y-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Titulaire</p>
                        <h2 class="mt-2 text-2xl font-semibold text-slate-950">{{ $holderName }}</h2>
                    </div>

                    <div class="space-y-2">
                        @foreach($detailLines as $line)
                            <p class="text-sm leading-6 text-slate-600">{{ $line }}</p>
                        @endforeach
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Code carte</p>
                        <p class="mt-2 text-base font-semibold text-slate-950">{{ $token }}</p>
                        <p class="mt-2 text-xs leading-5 text-slate-500">
                            Le QR contient uniquement un token interne securise, sans donnees personnelles directes.
                        </p>
                    </div>
                </div>

                <div class="rounded-[28px] border border-slate-200 bg-slate-50 px-4 py-4">
                    <div class="aspect-square overflow-hidden rounded-[24px] border border-white bg-white p-3 shadow-sm">
                        <div class="h-full w-full [&_svg]:h-full [&_svg]:w-full [&_svg]:object-contain">
                            {!! $qrSvg !!}
                        </div>
                    </div>
                    <p class="mt-4 text-center text-xs leading-5 text-slate-500">
                        QR de service pour controle interne et pointage.
                    </p>
                </div>
            </div>
        </section>
    </div>
</body>
</html>
