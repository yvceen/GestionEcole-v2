@extends('layouts.guest')

@section('content')
<div class="mx-auto max-w-lg rounded-3xl border border-amber-200 bg-amber-50 p-6 text-amber-900 shadow-sm">
    <h1 class="text-xl font-semibold">Etablissement inactif</h1>
    <p class="mt-2 text-sm">
        Votre Établissement n'est pas encore active. Veuillez contacter l'administration.
    </p>
    <div class="mt-5">
        <a href="{{ route('login') }}" class="inline-flex items-center rounded-2xl bg-black px-4 py-2 text-sm font-semibold text-white hover:bg-slate-900">
            Retour a la connexion
        </a>
    </div>
</div>
@endsection
