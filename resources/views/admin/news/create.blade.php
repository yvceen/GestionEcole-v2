@php
    $routePrefix = $routePrefix ?? 'admin.news';
    $layoutComponent = $layoutComponent ?? 'admin-layout';
@endphp

<x-dynamic-component :component="$layoutComponent" title="Nouvelle actualite">
    <x-ui.page-header
        title="Nouvelle actualite"
        subtitle="Preparez une publication moderne, lisible et bien ciblee pour les familles."
    >
        <x-slot name="actions">
            <x-ui.button :href="route($routePrefix . '.index')" variant="secondary">Retour</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <form method="POST" action="{{ route($routePrefix . '.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @include('admin.news.partials.form', ['news' => $news])
    </form>
</x-dynamic-component>
