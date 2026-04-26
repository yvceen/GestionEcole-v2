@php
    $routePrefix = $routePrefix ?? 'admin.news';
    $layoutComponent = $layoutComponent ?? 'admin-layout';
@endphp

<x-dynamic-component :component="$layoutComponent" title="Modifier l actualite">
    <x-ui.page-header
        title="Modifier l actualite"
        subtitle="Ajustez le contenu, la mise en avant et l audience de cette publication."
    >
        <x-slot name="actions">
            <x-ui.button :href="route($routePrefix . '.index')" variant="secondary">Retour</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <form method="POST" action="{{ route($routePrefix . '.update', $news) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')
        @include('admin.news.partials.form', ['news' => $news])
    </form>
</x-dynamic-component>
