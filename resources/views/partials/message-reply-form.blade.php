@php
    $replyAction = $replyAction ?? null;
    $replyRecipient = $replyRecipient ?? null;
    $replyLabel = $replyLabel ?? 'Repondre';
@endphp

@if($replyAction && $replyRecipient)
    <form method="POST" action="{{ $replyAction }}" class="space-y-4">
        @csrf
        <input type="hidden" name="reply_to_id" value="{{ $replyToId }}">

        @if($errors->any())
            <x-ui.alert variant="error">
                <ul class="space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-ui.alert>
        @endif

        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
            Reponse adressee a <span class="font-semibold text-slate-900">{{ $replyRecipient->name }}</span>
            <span class="text-xs text-slate-500">({{ $replyRecipient->role }})</span>
        </div>

        <x-ui.input
            name="subject"
            label="Sujet"
            :value="old('subject', $replySubject ?? '')"
            placeholder="Sujet de la reponse"
        />

        <x-ui.textarea
            name="body"
            label="Reponse"
            rows="5"
            placeholder="Ecrivez votre reponse..."
        >{{ old('body') }}</x-ui.textarea>

        <div class="flex items-center gap-3">
            <x-ui.button type="submit" variant="primary">{{ $replyLabel }}</x-ui.button>
        </div>
    </form>
@endif
