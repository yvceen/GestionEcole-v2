@php
    $routePrefix = $routePrefix ?? 'admin.messages';
    $layoutComponent = $layoutComponent ?? 'admin-layout';
@endphp

<x-dynamic-component :component="$layoutComponent" title="Messages en attente">
    <section class="overflow-hidden rounded-[32px] border border-slate-200 bg-[radial-gradient(circle_at_top_right,_rgba(251,191,36,0.28),_transparent_32%),linear-gradient(135deg,#0f172a,#1e1b4b_55%,#92400e)] px-6 py-6 text-white shadow-xl shadow-slate-200/70 md:px-8">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="max-w-2xl">
                <div class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-amber-100">
                    Validation
                </div>
                <h1 class="mt-4 text-3xl font-semibold tracking-tight md:text-4xl">Messages en attente</h1>
                <p class="mt-3 text-sm leading-6 text-amber-50/90">
                    Consultez les messages soumis a validation avant de les rendre visibles.
                </p>
            </div>

            <x-ui.button :href="route($routePrefix . '.index')" variant="secondary">
                Retour
            </x-ui.button>
        </div>
    </section>

    <section class="mt-6 overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-6 py-5">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-semibold tracking-tight text-slate-950">File de validation</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $pending->total() }} message(s) a traiter</p>
                </div>
                <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-50 text-amber-700 ring-1 ring-amber-100">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v5l3 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                </span>
            </div>
        </div>

        <div class="divide-y divide-slate-100">
            @forelse(($pending ?? collect()) as $message)
                <article class="px-6 py-5 transition hover:bg-amber-50/35">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="flex min-w-0 gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-amber-100 text-sm font-bold text-amber-700">
                                {{ mb_strtoupper(mb_substr($message->sender?->name ?? 'ME', 0, 2)) }}
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-950">
                                    {{ $message->sender?->name ?? ('Utilisateur #'.$message->sender_id) }}
                                </p>
                                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
                                    {{ \Illuminate\Support\Str::limit(strip_tags($message->bodyText()), 220) }}
                                </p>
                                <p class="mt-2 text-xs text-slate-400">
                                    {{ optional($message->created_at)->format('d/m/Y H:i') }}
                                </p>
                            </div>
                        </div>

                        <div class="flex shrink-0 flex-wrap justify-end gap-2">
                            <x-ui.button :href="route($routePrefix . '.show', $message)" variant="secondary" size="sm">
                                Ouvrir
                            </x-ui.button>

                            <form method="POST" action="{{ route($routePrefix . '.approve', $message) }}" onsubmit="return confirm('Approuver ce message ?');">
                                @csrf
                                <x-ui.button type="submit" variant="primary" size="sm">
                                    Approuver
                                </x-ui.button>
                            </form>

                            <form method="POST" action="{{ route($routePrefix . '.reject', $message) }}" onsubmit="return confirm('Refuser ce message ?');">
                                @csrf
                                <input type="hidden" name="reason" value="Refuse par l'administration">
                                <x-ui.button type="submit" variant="danger" size="sm">
                                    Refuser
                                </x-ui.button>
                            </form>
                        </div>
                    </div>
                </article>
            @empty
                <div class="grid min-h-[20rem] place-items-center px-6 py-12 text-center">
                    <div>
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/>
                            </svg>
                        </div>
                        <p class="mt-4 text-sm font-semibold text-slate-950">Aucun message en attente</p>
                        <p class="mt-1 text-sm text-slate-500">La file de validation est vide.</p>
                    </div>
                </div>
            @endforelse
        </div>

        <div class="border-t border-slate-100 px-6 py-4">
            {{ $pending->links() }}
        </div>
    </section>
</x-dynamic-component>
