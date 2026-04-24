@php
    $threadMessages = collect($threadMessages ?? []);
    $currentUserId = (int) ($currentUserId ?? 0);
@endphp

<div class="space-y-4">
    @foreach($threadMessages as $threadMessage)
        @php
            $isMine = (int) ($threadMessage->sender_id ?? 0) === $currentUserId;
            $statusVariant = match ($threadMessage->status ?? 'approved') {
                'pending' => 'warning',
                'approved' => 'success',
                'rejected' => 'danger',
                default => 'info',
            };
        @endphp

        <div class="flex {{ $isMine ? 'justify-end' : 'justify-start' }}">
            <article class="max-w-[92%] rounded-3xl border border-slate-200 px-4 py-3 shadow-sm {{ $isMine ? 'bg-sky-50' : 'bg-white' }}">
                <div class="flex flex-wrap items-center gap-2">
                    <p class="text-sm font-semibold text-slate-900">
                        {{ $isMine ? 'Vous' : ($threadMessage->sender?->name ?? ('Utilisateur #' . $threadMessage->sender_id)) }}
                    </p>
                    <x-ui.badge :variant="$statusVariant">{{ strtoupper($threadMessage->status ?? 'approved') }}</x-ui.badge>
                    <span class="text-[11px] text-slate-500">{{ optional($threadMessage->created_at)->format('d/m/Y H:i') }}</span>
                </div>

                <div class="mt-3 text-sm leading-6 text-slate-700">
                    {!! nl2br(e($threadMessage->bodyText())) !!}
                </div>

                @if(($threadMessage->status ?? null) === 'pending')
                    <p class="mt-3 text-xs font-medium text-amber-700">En attente de validation admin avant affichage au destinataire.</p>
                @endif

                @if(($threadMessage->status ?? null) === 'rejected')
                    <div class="mt-3 rounded-2xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700">
                        <span class="font-semibold">Motif :</span> {{ $threadMessage->rejection_reason ?: 'Non specifie' }}
                    </div>
                @endif
            </article>
        </div>
    @endforeach
</div>
