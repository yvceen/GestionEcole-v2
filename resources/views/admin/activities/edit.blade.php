@php
    $routePrefix = $routePrefix ?? 'admin.activities';
    $layoutComponent = $layoutComponent ?? 'admin-layout';
@endphp

<x-dynamic-component :component="$layoutComponent" title="Modifier activite" subtitle="Mise a jour des informations de l activite.">
    @include('partials.activities.form', [
        'action' => route($routePrefix . '.update', $activity),
        'method' => 'PUT',
        'submitLabel' => 'Mettre a jour',
    ])
</x-dynamic-component>
