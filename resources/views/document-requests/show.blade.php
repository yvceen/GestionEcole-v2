<x-dynamic-component :component="$layoutComponent" title="Suivi de la demande" subtitle="Détails et avancement du document demandé.">
    @php
        $variant = match($documentRequest->status) {'ready', 'delivered' => 'success', 'rejected', 'cancelled' => 'danger', 'processing' => 'info', default => 'warning'};
        $steps = ['pending' => 'Demande reçue', 'processing' => 'Préparation', 'ready' => 'Document prêt', 'delivered' => 'Document remis'];
        $currentStep = array_search($documentRequest->status, array_keys($steps), true);
    @endphp
    <section class="rounded-[28px] border border-sky-100 bg-gradient-to-r from-sky-50 via-white to-emerald-50 p-6 shadow-sm">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div><p class="text-xs font-bold uppercase tracking-[0.18em] text-sky-700">Demande #{{ $documentRequest->id }}</p><h1 class="mt-2 text-2xl font-bold text-slate-950">{{ $documentRequest->type_label }}</h1><p class="mt-2 text-sm text-slate-600">{{ $documentRequest->student?->full_name }} · {{ $documentRequest->student?->classroom?->name }}</p></div>
            <x-ui.badge :variant="$variant">{{ \App\Models\DocumentRequest::statuses()[$documentRequest->status] ?? $documentRequest->status }}</x-ui.badge>
        </div>
        @if(!in_array($documentRequest->status, ['rejected', 'cancelled'], true))
            <div class="mt-6 grid gap-3 md:grid-cols-4">
                @foreach($steps as $key => $label)
                    @php($stepIndex = $loop->index)
                    <div class="rounded-2xl border p-4 {{ $currentStep !== false && $stepIndex <= $currentStep ? 'border-emerald-200 bg-emerald-50' : 'border-slate-200 bg-white' }}"><p class="text-xs font-bold uppercase tracking-wide {{ $currentStep !== false && $stepIndex <= $currentStep ? 'text-emerald-700' : 'text-slate-400' }}">Étape {{ $loop->iteration }}</p><p class="mt-2 font-bold text-slate-900">{{ $label }}</p></div>
                @endforeach
            </div>
        @endif
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_minmax(320px,.65fr)]">
        <x-ui.card title="Détails de la demande" subtitle="Toutes les informations utiles à la préparation.">
            <dl class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-2xl bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Élève</dt><dd class="mt-2 font-bold text-slate-900">{{ $documentRequest->student?->full_name }}</dd></div>
                <div class="rounded-2xl bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Parent</dt><dd class="mt-2 font-bold text-slate-900">{{ $documentRequest->parent?->name ?? 'Non renseigné' }}</dd></div>
                <div class="rounded-2xl bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Format</dt><dd class="mt-2 font-bold text-slate-900">{{ strtoupper($documentRequest->language) }} · {{ $documentRequest->copies }} exemplaire(s)</dd></div>
                <div class="rounded-2xl bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Remise</dt><dd class="mt-2 font-bold text-slate-900">{{ $documentRequest->delivery_method === 'digital' ? 'Document numérique' : 'Retrait à l’école' }}</dd></div>
                <div class="rounded-2xl bg-slate-50 p-4 sm:col-span-2"><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Précision</dt><dd class="mt-2 text-sm leading-6 text-slate-700">{{ $documentRequest->purpose ?: 'Aucune précision.' }}</dd></div>
                @if($documentRequest->admin_note)<div class="rounded-2xl border border-sky-100 bg-sky-50 p-4 sm:col-span-2"><dt class="text-xs font-bold uppercase tracking-wide text-sky-700">Message de l’administration</dt><dd class="mt-2 text-sm leading-6 text-slate-700">{{ $documentRequest->admin_note }}</dd></div>@endif
                @if($documentRequest->rejection_reason)<div class="rounded-2xl border border-rose-100 bg-rose-50 p-4 sm:col-span-2"><dt class="text-xs font-bold uppercase tracking-wide text-rose-700">Motif du refus</dt><dd class="mt-2 text-sm leading-6 text-slate-700">{{ $documentRequest->rejection_reason }}</dd></div>@endif
            </dl>
            <div class="mt-5 flex flex-wrap gap-3">
                @if($documentRequest->file_path)<x-ui.button :href="route($routePrefix.'.download', $documentRequest)" variant="primary">Télécharger le document</x-ui.button>@endif
                @if(!$canManage && $documentRequest->status === 'pending')<form method="POST" action="{{ route($routePrefix.'.cancel', $documentRequest) }}">@csrf @method('PUT')<x-ui.button type="submit" variant="secondary">Annuler la demande</x-ui.button></form>@endif
            </div>
        </x-ui.card>

        @if($canManage)
            <x-ui.card title="Traiter la demande" subtitle="Mettez à jour le statut et joignez le document final.">
                <form method="POST" action="{{ route($routePrefix.'.update', $documentRequest) }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf @method('PUT')
                    <label class="grid gap-2 text-sm font-semibold text-slate-700">Statut<select name="status" class="app-input">@foreach(\App\Models\DocumentRequest::statuses() as $value => $label)<option value="{{ $value }}" @selected($documentRequest->status === $value)>{{ $label }}</option>@endforeach</select></label>
                    <label class="grid gap-2 text-sm font-semibold text-slate-700">Message au parent<textarea name="admin_note" rows="4" class="app-input">{{ old('admin_note', $documentRequest->admin_note) }}</textarea></label>
                    <label class="grid gap-2 text-sm font-semibold text-slate-700">Motif du refus<textarea name="rejection_reason" rows="3" class="app-input">{{ old('rejection_reason', $documentRequest->rejection_reason) }}</textarea></label>
                    <label class="grid gap-2 text-sm font-semibold text-slate-700">Document final PDF ou image<input type="file" name="document_file" accept=".pdf,.jpg,.jpeg,.png" class="app-input"></label>
                    @if($errors->any())<div class="rounded-xl bg-rose-50 p-3 text-sm text-rose-700">{{ $errors->first() }}</div>@endif
                    <x-ui.button type="submit" variant="primary">Enregistrer la mise à jour</x-ui.button>
                </form>
            </x-ui.card>
        @else
            <x-ui.card title="Besoin d’aide ?" subtitle="L’administration vous informera dès que le document sera prêt.">
                <p class="text-sm leading-6 text-slate-600">Pour toute précision, contactez l’établissement en indiquant le numéro de demande <strong>#{{ $documentRequest->id }}</strong>.</p>
            </x-ui.card>
        @endif
    </div>
</x-dynamic-component>
