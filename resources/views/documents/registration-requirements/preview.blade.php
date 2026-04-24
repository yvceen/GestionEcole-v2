@php
    $logoPath = is_string($school?->logo_path ?? null) ? ltrim((string) $school->logo_path, '/') : '';
    $logoUrl = null;
    if ($logoPath !== '') {
        if (\Illuminate\Support\Str::startsWith($logoPath, ['http://', 'https://'])) {
            $logoUrl = $logoPath;
        } elseif (\Illuminate\Support\Facades\Storage::disk('public')->exists($logoPath)) {
            $logoUrl = asset('storage/' . $logoPath);
        }
    }
@endphp
<x-dynamic-component :component="$context['layout']" :title="$context['title']" :subtitle="$context['subtitle']">
    <x-ui.page-header
        title="Apercu imprimable"
        subtitle="Version A4 prete pour impression ou export PDF."
    >
        <div class="flex flex-wrap gap-3">
            <x-ui.button :href="route($context['routes']['index'])" variant="secondary">Retour aux reglages</x-ui.button>
            <x-ui.button :href="route($context['routes']['pdf'])" variant="primary">Telecharger PDF</x-ui.button>
            <button type="button" onclick="window.print()" class="app-button-secondary rounded-full px-5 py-3 text-sm font-semibold">Imprimer</button>
        </div>
    </x-ui.page-header>

    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; }
        }
    </style>

    <div class="mx-auto max-w-4xl rounded-[32px] border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 bg-[linear-gradient(135deg,rgba(14,165,233,0.08),rgba(255,255,255,0.98))] px-8 py-8">
            <div class="flex items-start justify-between gap-6">
                <div class="flex items-center gap-4">
                    @if($logoUrl)
                        <img src="{{ $logoUrl }}" alt="Logo {{ $school?->name ?? 'MyEdu' }}" class="h-16 w-16 rounded-2xl border border-slate-200 bg-white object-cover p-2">
                    @endif
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-700">MyEdu | {{ $context['portal'] }}</p>
                        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">{{ $school?->name ?? 'MyEdu' }}</h1>
                        <p class="mt-2 text-sm text-slate-600">Liste des pieces et fournitures d inscription a remettre aux familles.</p>
                    </div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-right">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Document</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900">Version imprimable A4</p>
                </div>
            </div>
        </div>

        <div class="space-y-8 px-8 py-8">
            @foreach($groupedItems as $category => $items)
                <section>
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-semibold text-slate-950">{{ $category }}</h2>
                            <p class="text-sm text-slate-500">{{ $items->count() }} element(s)</p>
                        </div>
                    </div>
                    <div class="space-y-3">
                        @foreach($items as $item)
                            <div class="rounded-3xl border border-slate-200 px-5 py-4">
                                <div class="flex items-start gap-4">
                                    <div class="mt-1 h-5 w-5 rounded-md border-2 {{ $item->is_required ? 'border-sky-500' : 'border-slate-300' }}"></div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-3">
                                            <p class="text-base font-semibold text-slate-900">{{ $item->label }}</p>
                                            <span class="rounded-full {{ $item->is_required ? 'bg-sky-100 text-sky-700' : 'bg-slate-100 text-slate-600' }} px-3 py-1 text-xs font-semibold">
                                                {{ $item->is_required ? 'Obligatoire' : 'Optionnel' }}
                                            </span>
                                        </div>
                                        @if(filled($item->notes))
                                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $item->notes }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>

        <div class="border-t border-slate-200 bg-slate-50 px-8 py-5 text-sm text-slate-500">
            Document genere depuis MyEdu. Les equipes administratives peuvent l imprimer ou l exporter en PDF.
        </div>
    </div>
</x-dynamic-component>
