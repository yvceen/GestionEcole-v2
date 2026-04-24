<x-school-life-layout title="Detail du devoir" subtitle="Consultez le contenu, les pieces jointes et les actions de moderation.">
    @php
        $routePrefix = $routePrefix ?? 'school-life.homeworks';
        $normalized = $homework->normalized_status ?? 'pending';
        $variant = match($normalized) {
            'approved' => 'success',
            'rejected' => 'danger',
            default => 'warning',
        };
    @endphp

    <x-ui.page-header
        title="Detail du devoir"
        subtitle="Consultez le contenu, les pieces jointes et les actions de validation."
    >
        <x-slot name="actions">
            <x-ui.button :href="route($routePrefix . '.index')" variant="secondary">Retour</x-ui.button>
            <x-ui.button :href="route($routePrefix . '.edit', $homework)" variant="ghost">Modifier</x-ui.button>

            @if($normalized === 'pending')
                <form method="POST" action="{{ route($routePrefix . '.approve', $homework) }}">
                    @csrf
                    <x-ui.button type="submit" variant="outline">Approuver</x-ui.button>
                </form>
                <form method="POST" action="{{ route($routePrefix . '.reject', $homework) }}">
                    @csrf
                    <x-ui.button type="submit" variant="danger">Rejeter</x-ui.button>
                </form>
            @endif

            <form method="POST" action="{{ route($routePrefix . '.destroy', $homework) }}">
                @csrf
                @method('DELETE')
                <x-ui.button type="submit" variant="danger" onclick="return confirm('Supprimer ce devoir ?');">Supprimer</x-ui.button>
            </form>
        </x-slot>
    </x-ui.page-header>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1.4fr)_320px]">
        <x-ui.card>
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">{{ $homework->title }}</h2>
                    <p class="mt-1 text-sm text-slate-500">Classe : {{ $homework->classroom?->name ?? '-' }}</p>
                </div>
                <x-ui.badge :variant="$variant">{{ strtoupper($normalized) }}</x-ui.badge>
            </div>

            <dl class="mt-5 grid gap-4 text-sm text-slate-700 sm:grid-cols-2">
                <div><dt class="text-xs uppercase tracking-wide text-slate-500">Enseignant</dt><dd class="mt-1 font-medium">{{ $homework->teacher?->name ?? '-' }}</dd></div>
                <div><dt class="text-xs uppercase tracking-wide text-slate-500">Echeance</dt><dd class="mt-1 font-medium">{{ optional($homework->due_at)->format('Y-m-d H:i') ?? '-' }}</dd></div>
                <div><dt class="text-xs uppercase tracking-wide text-slate-500">Cree le</dt><dd class="mt-1 font-medium">{{ optional($homework->created_at)->format('Y-m-d H:i') ?? '-' }}</dd></div>
                <div><dt class="text-xs uppercase tracking-wide text-slate-500">Approuve le</dt><dd class="mt-1 font-medium">{{ optional($homework->approved_at)->format('Y-m-d H:i') ?? '-' }}</dd></div>
                <div><dt class="text-xs uppercase tracking-wide text-slate-500">Rejete le</dt><dd class="mt-1 font-medium">{{ optional($homework->rejected_at)->format('Y-m-d H:i') ?? '-' }}</dd></div>
            </dl>

            @if($homework->description)
                <div class="mt-6 whitespace-pre-line rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">{{ $homework->description }}</div>
            @endif
        </x-ui.card>

        <x-ui.card title="Pieces jointes" subtitle="Documents associes au devoir.">
            @if($homework->attachments->count())
                <ul class="space-y-2 text-sm">
                    @foreach($homework->attachments as $attachment)
                        <li class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 px-3 py-2 text-slate-700">
                            <span>{{ $attachment->original_name }} <span class="text-xs text-slate-500">({{ number_format(((int) $attachment->size) / 1024, 1) }} KB)</span></span>
                            <x-ui.button :href="route($routePrefix . '.attachments.download', $attachment)" variant="ghost" size="sm" target="_blank">Telecharger</x-ui.button>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-sm text-slate-500">Aucune piece jointe.</p>
            @endif
        </x-ui.card>
    </div>
</x-school-life-layout>
