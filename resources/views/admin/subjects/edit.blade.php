<x-admin-layout title="Modifier une matiere">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Modifier une matiere</h1>
            <p class="mt-1 text-sm text-slate-500">Mettez a jour la fiche et gerez l affectation enseignants sans quitter le module Matieres.</p>
        </div>
        <a href="{{ route('admin.subjects.index') }}" class="text-sm font-semibold text-slate-700 hover:underline">Retour</a>
    </div>

    <form method="POST" action="{{ route('admin.subjects.update', $subject) }}" class="mt-8 space-y-6">
        @csrf
        @method('PUT')
        @include('admin.subjects.partials.form', ['subject' => $subject])
    </form>
</x-admin-layout>
