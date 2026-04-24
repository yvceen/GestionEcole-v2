<x-admin-layout title="Nouvelle actualite">
    <x-ui.page-header
        title="Nouvelle actualite"
        subtitle="Preparez une publication moderne, lisible et bien ciblee pour les familles."
    >
        <x-slot name="actions">
            <x-ui.button :href="route('admin.news.index')" variant="secondary">Retour</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <form method="POST" action="{{ route('admin.news.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @include('admin.news.partials.form', ['news' => $news])
    </form>
</x-admin-layout>
