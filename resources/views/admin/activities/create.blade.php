@php
    $routePrefix = $routePrefix ?? 'admin.activities';
    $layoutComponent = $layoutComponent ?? 'admin-layout';
@endphp

<x-dynamic-component :component="$layoutComponent" title="Nouvelle activite" subtitle="Ajout d une activite scolaire dans le calendrier operationnel.">
    @include('partials.activities.form', [
        'action' => route($routePrefix . '.store'),
        'submitLabel' => 'Creer activite',
    ])
</x-dynamic-component>
