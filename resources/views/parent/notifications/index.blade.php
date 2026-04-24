<x-parent-layout title="Notifications" subtitle="Retrouvez les alertes et mises a jour utiles a votre suivi parent dans un historique clair.">
    <section class="student-panel">
        <div class="space-y-3">
            @forelse($notifications as $notification)
                <article class="rounded-2xl border border-slate-200 bg-white p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">{{ $notification->title }}</h3>
                            <p class="mt-1 text-sm text-slate-700">{{ $notification->body }}</p>
                            <p class="mt-2 text-xs text-slate-500">{{ optional($notification->created_at)->format('d/m/Y H:i') }}</p>
                        </div>
                        @if($notification->read_at)
                            <span class="rounded-xl border border-emerald-200 bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700">Lu</span>
                        @else
                            <span class="rounded-xl border border-amber-200 bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-700">Nouveau</span>
                        @endif
                    </div>

                    <div class="mt-3 flex items-center gap-2">
                        <a href="{{ route('parent.notifications.open', $notification) }}" class="app-button-primary rounded-xl px-3 py-2 text-xs font-semibold">
                            Ouvrir
                        </a>
                        @if(!$notification->read_at)
                            <form method="POST" action="{{ route('parent.notifications.read', $notification) }}" data-loading-label="Mise a jour de la notification...">
                                @csrf
                                <button class="app-button-secondary rounded-xl px-3 py-2 text-xs font-semibold">
                                    Marquer lu
                                </button>
                            </form>
                        @endif
                    </div>
                </article>
            @empty
                <div class="student-empty">Aucune notification a afficher.</div>
            @endforelse
        </div>
    </section>

    <div class="mt-5">
        {{ $notifications->links() }}
    </div>
</x-parent-layout>
