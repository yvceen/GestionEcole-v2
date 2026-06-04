@php($isOpen = $digitalAuthorization->status !== 'closed' && (!$digitalAuthorization->due_at || $digitalAuthorization->due_at->isFuture()))
<x-dynamic-component :component="$layoutComponent" :title="$digitalAuthorization->title" subtitle="Détail de la demande et suivi des réponses parentales.">
    @if(session('success'))<x-ui.alert variant="success">{{ session('success') }}</x-ui.alert>@endif
    <section class="rounded-[28px] border border-sky-100 bg-gradient-to-r from-sky-50 via-white to-emerald-50 p-6 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="max-w-3xl"><p class="text-xs font-bold uppercase tracking-[0.18em] text-sky-700">{{ \App\Models\DigitalAuthorization::categories()[$digitalAuthorization->category] ?? 'Autorisation' }}</p><h1 class="mt-2 text-2xl font-bold text-slate-950">{{ $digitalAuthorization->title }}</h1><p class="mt-4 whitespace-pre-line text-sm leading-7 text-slate-700">{{ $digitalAuthorization->description }}</p></div>
            <div class="flex flex-wrap gap-2"><x-ui.button :href="route($routePrefix.'.index')" variant="secondary">Retour</x-ui.button>@if($canManage && $digitalAuthorization->status !== 'closed')<form method="POST" action="{{ route($routePrefix.'.close', $digitalAuthorization) }}">@csrf @method('PUT')<x-ui.button type="submit" variant="secondary">Clôturer</x-ui.button></form>@endif</div>
        </div>
        <div class="mt-5 flex flex-wrap gap-2 text-xs font-semibold text-slate-700">@if($digitalAuthorization->event_at)<span class="rounded-full bg-white px-3 py-2">Activité : {{ $digitalAuthorization->event_at->format('d/m/Y H:i') }}</span>@endif @if($digitalAuthorization->due_at)<span class="rounded-full bg-white px-3 py-2">Limite : {{ $digitalAuthorization->due_at->format('d/m/Y H:i') }}</span>@endif <span class="rounded-full bg-white px-3 py-2">{{ $digitalAuthorization->status === 'closed' ? 'Clôturée' : 'Ouverte' }}</span></div>
        @if($digitalAuthorization->instructions)<div class="mt-5 rounded-2xl border border-sky-100 bg-white p-4"><p class="text-xs font-bold uppercase tracking-wide text-sky-700">Informations pratiques</p><p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $digitalAuthorization->instructions }}</p></div>@endif
    </section>

    @if($isParent)
        <section class="grid gap-5">
            @foreach($recipients as $recipient)
                <div class="rounded-[26px] border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-3"><div><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Élève concerné</p><h2 class="mt-1 text-xl font-bold text-slate-950">{{ $recipient->student?->full_name }}</h2><p class="text-sm text-slate-500">{{ $recipient->student?->classroom?->name }}</p></div><x-ui.badge :variant="$recipient->status === 'approved' ? 'success' : ($recipient->status === 'declined' ? 'danger' : 'warning')">{{ $recipient->status === 'approved' ? 'Acceptée' : ($recipient->status === 'declined' ? 'Refusée' : 'Réponse attendue') }}</x-ui.badge></div>
                    @if($isOpen)
                    <form method="POST" action="{{ route($routePrefix.'.respond', $recipient) }}" class="mt-6 space-y-4">@csrf
                        <div class="grid gap-3 sm:grid-cols-2">
                            <label class="cursor-pointer rounded-2xl border border-emerald-200 bg-emerald-50 p-4"><input type="radio" name="decision" value="approved" required @checked($recipient->status === 'approved')> <span class="ml-2 font-bold text-emerald-900">J’autorise</span></label>
                            <label class="cursor-pointer rounded-2xl border border-rose-200 bg-rose-50 p-4"><input type="radio" name="decision" value="declined" required @checked($recipient->status === 'declined')> <span class="ml-2 font-bold text-rose-900">Je refuse</span></label>
                        </div>
                        <x-ui.input name="signed_name" label="Nom complet du parent signataire" :value="old('signed_name', $recipient->signed_name ?: auth()->user()?->name)" required />
                        <x-ui.textarea name="response_comment" label="Remarque {{ $digitalAuthorization->requires_comment ? '(obligatoire)' : '(optionnelle)' }}" rows="3">{{ old('response_comment', $recipient->response_comment) }}</x-ui.textarea>
                        <label class="flex items-start gap-3 rounded-2xl border border-sky-100 bg-sky-50 p-4"><input type="checkbox" name="confirmation" value="1" required><span class="text-sm leading-6 text-slate-700">Je confirme être le parent ou responsable légal et valide cette réponse numérique.</span></label>
                        <x-ui.button type="submit" variant="primary">Enregistrer ma réponse</x-ui.button>
                    </form>
                    @else
                        <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm font-semibold text-slate-600">Cette autorisation n’accepte plus de nouvelles réponses.</div>
                    @endif
                </div>
            @endforeach
        </section>
    @else
        <x-ui.card title="Réponses des familles" subtitle="Consultez rapidement les accords, refus et réponses manquantes.">
            <div class="overflow-x-auto rounded-2xl border border-slate-200"><table class="app-table min-w-[900px]"><thead><tr><th>Élève</th><th>Parent</th><th>Classe</th><th>Décision</th><th>Signature</th><th>Réponse</th></tr></thead><tbody>
                @foreach($recipients as $recipient)<tr><td class="font-semibold text-slate-950">{{ $recipient->student?->full_name }}</td><td>{{ $recipient->parentUser?->name ?? '-' }}<div class="text-xs text-slate-500">{{ $recipient->parentUser?->phone }}</div></td><td>{{ $recipient->student?->classroom?->name ?? '-' }}</td><td><x-ui.badge :variant="$recipient->status === 'approved' ? 'success' : ($recipient->status === 'declined' ? 'danger' : 'warning')">{{ $recipient->status === 'approved' ? 'Acceptée' : ($recipient->status === 'declined' ? 'Refusée' : 'En attente') }}</x-ui.badge></td><td>{{ $recipient->signed_name ?: '-' }}</td><td>{{ $recipient->responded_at?->format('d/m/Y H:i') ?? '-' }}@if($recipient->response_comment)<div class="mt-1 max-w-xs text-xs text-slate-500">{{ $recipient->response_comment }}</div>@endif</td></tr>@endforeach
            </tbody></table></div>
        </x-ui.card>
    @endif
</x-dynamic-component>
