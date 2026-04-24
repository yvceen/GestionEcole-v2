<x-admin-layout title="Messages en attente">
    <x-ui.page-header
        title="Validation des messages"
        subtitle="Examinez les messages soumis à validation et traitez-les rapidement."
    >
        <x-slot name="actions">
            <x-ui.button :href="route('admin.messages.index')" variant="secondary">
                Retour à la messagerie
            </x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <section class="space-y-4">
        @forelse(($pending ?? collect()) as $message)
            <x-ui.card>
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Expéditeur</p>
                        <p class="mt-1 text-lg font-semibold text-slate-900">
                            {{ $message->sender?->name ?? ('Utilisateur #'.$message->sender_id) }}
                        </p>
                        <p class="mt-2 text-sm font-medium text-slate-800">{{ $message->subject ?: '(Sans sujet)' }}</p>
                        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                            {{ \Illuminate\Support\Str::limit(strip_tags($message->body ?? ''), 180) }}
                        </p>
                    </div>

                    <div class="shrink-0 text-xs text-slate-500">
                        {{ optional($message->created_at)->format('d/m/Y H:i') }}
                    </div>
                </div>

                <div class="mt-5 flex flex-wrap justify-end gap-2">
                    <x-ui.button :href="route('admin.messages.show', $message)" variant="secondary" size="sm">
                        Voir le détail
                    </x-ui.button>

                    <form method="POST" action="{{ route('admin.messages.approve', $message) }}" onsubmit="return confirm('Approuver ce message ?');">
                        @csrf
                        <x-ui.button type="submit" variant="outline" size="sm">
                            Approuver
                        </x-ui.button>
                    </form>

                    <form method="POST" action="{{ route('admin.messages.reject', $message) }}" onsubmit="return confirm('Refuser ce message ?');">
                        @csrf
                        <input type="hidden" name="reason" value="Refusé par l'administration">
                        <x-ui.button type="submit" variant="danger" size="sm">
                            Refuser
                        </x-ui.button>
                    </form>
                </div>
            </x-ui.card>
        @empty
            <x-ui.card>
                <p class="text-sm text-slate-500">Aucun message en attente de validation.</p>
            </x-ui.card>
        @endforelse
    </section>

    <div class="mt-4">
        {{ $pending->links() }}
    </div>
</x-admin-layout>
