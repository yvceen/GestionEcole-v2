@php
    $recipientsCollection = collect($recipients ?? [])->values();

    $tabs = [
        [
            'key' => 'staff',
            'label' => 'Personnel',
            'description' => 'Choisissez un administrateur ou un enseignant.',
            'items' => $recipientsCollection->map(function ($recipient) {
                $label = $recipient->name ?? 'Personne #' . $recipient->id;
                $metaParts = [];
                if (!empty($recipient->role)) {
                    $metaParts[] = ucfirst($recipient->role);
                }
                if (!empty($recipient->email)) {
                    $metaParts[] = $recipient->email;
                }
                return [
                    'id' => $recipient->id,
                    'label' => $label,
                    'meta' => implode(' · ', $metaParts),
                ];
            })->values()->all(),
            'multi' => false,
            'field' => 'recipient_id',
            'initialSelected' => collect([old('recipient_id')])->filter(fn ($value) => !is_null($value) && $value !== '')->values()->all(),
        ],
    ];
@endphp

<x-parent-layout title="Nouveau message (Parent)">
    <x-messaging-composer
        action="{{ route('parent.messages.store') }}"
        back-url="{{ route('parent.messages.index') }}"
        title="Nouveau message"
        subtitle="Envoyez un message à un admin ou un enseignant."
        :tabs="$tabs"
    />
</x-parent-layout>
