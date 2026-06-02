@php
    $routePrefix = $routePrefix ?? 'admin.activities';
    $layoutComponent = $layoutComponent ?? 'admin-layout';
@endphp

<x-dynamic-component :component="$layoutComponent" title="Nouvelle activité" subtitle="Ajout d une activité scolaire dans le calendrier operationnel.">
    @include('partials.activities.form', [
        'action' => route($routePrefix . '.store'),
        'submitLabel' => 'Creer activité',
    ])
</x-dynamic-component>
