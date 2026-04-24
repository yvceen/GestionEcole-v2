<x-admin-layout title="Creer une matiere">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Creer une matiere</h1>
            <p class="mt-1 text-sm text-slate-500">Centralisez la matiere, son statut et l affectation des enseignants depuis le meme ecran.</p>
        </div>
        <a href="{{ route('admin.subjects.index') }}" class="text-sm font-semibold text-slate-700 hover:underline">Retour</a>
    </div>

    <form method="POST" action="{{ route('admin.subjects.store') }}" class="mt-8 space-y-6">
        @csrf
        @include('admin.subjects.partials.form', ['subject' => $subject])
    </form>
</x-admin-layout>
