@extends('layouts.guest')

@section('content')
<div class="mx-auto max-w-lg rounded-3xl border border-amber-200 bg-amber-50 p-6 text-amber-900 shadow-sm">
    <h1 class="text-xl font-semibold">Établissement inactif</h1>
    <p class="mt-2 text-sm">
        Votre établissement n'est pas encore activé. Veuillez contacter l'administration.
    </p>
    <div class="mt-5">
        <a href="{{ route('login') }}" class="inline-flex items-center rounded-2xl bg-black px-4 py-2 text-sm font-semibold text-white hover:bg-slate-900">
            Retour à la connexion
        </a>
    </div>
</div>
@endsection
