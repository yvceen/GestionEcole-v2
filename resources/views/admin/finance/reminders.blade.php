<x-admin-layout title="Rappels de paiement" subtitle="Programmez des relances propres, school-scopees et alignees sur les impayes reels.">
    @php
        $setting = $setting ?? null;
        $summary = $summary ?? [];
        $recipients = $recipients ?? collect();
        $defaultTemplate = app(\App\Services\PaymentReminderService::class)->defaultTemplate();
    @endphp

    <x-ui.page-header
        title="Rappels de paiement"
        subtitle="Configurez la date de relance, verifiez les familles ciblees et envoyez un rappel manuel si necessaire."
    >
        <x-slot name="actions">
            <form method="POST" action="{{ route('admin.finance.reminders.send_now') }}">
                @csrf
                <x-ui.button type="submit" variant="outline">
                    Envoyer maintenant
                </x-ui.button>
            </form>
            <x-ui.button :href="route('admin.finance.index')" variant="secondary">
                Retour finance
            </x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <section class="grid gap-4 md:grid-cols-4">
        <div class="app-stat-card">
            <p class="app-stat-label">Familles ciblees</p>
            <p class="app-stat-value">{{ (int) ($summary['count'] ?? 0) }}</p>
            <p class="app-stat-meta">Parents avec au moins un montant du ou en retard.</p>
        </div>
        <div class="app-stat-card">
            <p class="app-stat-label">Montant estime</p>
            <p class="app-stat-value">{{ number_format((float) ($summary['total_due'] ?? 0), 2) }}</p>
            <p class="app-stat-meta">MAD de soldes encore attendus.</p>
        </div>
        <div class="app-stat-card">
            <p class="app-stat-label">Montant en retard</p>
            <p class="app-stat-value">{{ number_format((float) ($summary['total_overdue'] ?? 0), 2) }}</p>
            <p class="app-stat-meta">MAD deja en retard sur des mois precedents.</p>
        </div>
        <div class="app-stat-card">
            <p class="app-stat-label">Dernier envoi</p>
            <p class="app-stat-value text-xl">{{ $setting?->last_sent_at?->format('d/m/Y H:i') ?? '-' }}</p>
            <p class="app-stat-meta">Evite les doubles envois automatiques le meme jour.</p>
        </div>
    </section>

    <section class="grid gap-6 lg:grid-cols-[minmax(0,420px)_minmax(0,1fr)]">
        <x-ui.card title="Configuration" subtitle="Activez le rappel mensuel et choisissez le jour cible.">
            <form method="POST" action="{{ route('admin.finance.reminders.update') }}" class="space-y-5">
                @csrf
                @method('PUT')

                <label class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                    <input type="hidden" name="is_enabled" value="0">
                    <input type="checkbox" name="is_enabled" value="1" @checked((bool) ($setting?->is_enabled ?? false)) class="mt-1 h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                    <span>
                        <span class="block text-sm font-semibold text-slate-900">Activer les rappels automatiques</span>
                        <span class="mt-1 block text-sm text-slate-500">Le scheduler Laravel verifiera chaque matin si la date du jour correspond au jour configure.</span>
                    </span>
                </label>

                <x-ui.input
                    label="Jour du mois"
                    name="reminder_day"
                    type="number"
                    min="1"
                    max="31"
                    :value="old('reminder_day', $setting?->reminder_day ?? 28)"
                />

                <x-ui.textarea
                    label="Modele du rappel"
                    name="message_template"
                    rows="8"
                >{{ old('message_template', $setting?->message_template ?: $defaultTemplate) }}</x-ui.textarea>

                <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-4 text-sm text-slate-600">
                    Variables disponibles :
                    <span class="font-semibold">{parent_name}</span>,
                    <span class="font-semibold">{school_name}</span>,
                    <span class="font-semibold">{total_due}</span>,
                    <span class="font-semibold">{overdue_total}</span>,
                    <span class="font-semibold">{unpaid_months}</span>,
                    <span class="font-semibold">{overdue_months}</span>.
                </div>

                <div class="flex justify-end gap-3">
                    <x-ui.button :href="route('admin.finance.reminders.edit')" variant="secondary">
                        Recharger l apercu
                    </x-ui.button>
                    <x-ui.button type="submit" variant="primary">
                        Enregistrer
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>

        <x-ui.card title="Apercu des destinataires" subtitle="Parents qui recevraient un rappel si vous l envoyiez maintenant.">
            <div class="space-y-3">
                @forelse($recipients as $recipient)
                    <article class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <p class="font-semibold text-slate-900">{{ $recipient['parent_name'] }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $recipient['parent_email'] ?: 'Email non renseigne' }}</p>
                                <p class="mt-2 text-sm text-slate-600">Enfants : {{ implode(', ', $recipient['children']) }}</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <x-ui.badge variant="warning">{{ $recipient['unpaid_months'] }} mois impayes</x-ui.badge>
                                <x-ui.badge variant="danger">{{ $recipient['overdue_months'] }} mois en retard</x-ui.badge>
                            </div>
                        </div>

                        <div class="mt-3 grid gap-3 md:grid-cols-2">
                            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Montant estime</p>
                                <p class="mt-2 text-lg font-semibold text-slate-950">{{ number_format((float) $recipient['total_due'], 2) }} MAD</p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Montant en retard</p>
                                <p class="mt-2 text-lg font-semibold text-slate-950">{{ number_format((float) $recipient['overdue_total'], 2) }} MAD</p>
                            </div>
                        </div>

                        <div class="mt-3 rounded-2xl border border-slate-200 bg-white px-4 py-4 text-sm leading-6 text-slate-700">
                            {{ $recipient['message_preview'] }}
                        </div>
                    </article>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-sm text-slate-500">
                        Aucun parent ne correspond actuellement a un rappel d impaye ou d arriere.
                    </div>
                @endforelse
            </div>
        </x-ui.card>
    </section>
</x-admin-layout>
