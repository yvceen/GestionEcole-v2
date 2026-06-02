@php
    $routePrefix = $routePrefix ?? 'admin.activities';
    $layoutComponent = $layoutComponent ?? 'admin-layout';
@endphp

<x-dynamic-component :component="$layoutComponent" title="Modifier activité" subtitle="Mise a jour des informations de l activité.">
    @include('partials.activities.form', [
        'action' => route($routePrefix . '.update', $activity),
        'method' => 'PUT',
        'submitLabel' => 'Mettre a jour',
    ])
</x-dynamic-component>
