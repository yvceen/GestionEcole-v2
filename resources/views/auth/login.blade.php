<x-guest-layout>
    @php
        $schoolContext = app()->bound('currentSchool') ? app('currentSchool') : null;
    @endphp

    <div class="text-center">
        <div class="mx-auto mb-5 grid h-14 w-14 place-items-center rounded-3xl bg-gradient-to-br from-slate-950 via-slate-800 to-teal-700 text-white shadow-[0_18px_35px_-24px_rgba(15,23,42,.9)]">
            <span class="text-lg font-semibold">ME</span>
        </div>
        <h1 class="text-3xl font-semibold tracking-tight text-slate-950">
            {{ $schoolContext ? $schoolContext->name : 'Bienvenue sur My Edu' }}
        </h1>
        <p class="mx-auto mt-3 max-w-sm text-sm leading-6 text-slate-600">
            {{ $schoolContext ? 'Connectez-vous a votre espace educatif en toute securite.' : 'Accedez a votre espace educatif et restez connecte a votre communaute scolaire.' }}
        </p>
    </div>

    @if(session('status'))
        <div class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-900">
            {{ session('status') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mt-5 rounded-2xl border border-red-200 bg-red-50 p-3 text-sm text-red-900">
            <ul class="ml-5 list-disc">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
        @csrf

        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-900">Adresse email</label>
            <input
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="username"
                placeholder="nom@ecole.ma"
                class="w-full rounded-2xl border-slate-200/80 bg-white/80 px-4 py-3 text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-teal-600 focus:ring-teal-600"
            >
        </div>

        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-900">Mot de passe</label>
            <input
                type="password"
                name="password"
                required
                autocomplete="current-password"
                placeholder="Votre mot de passe"
                class="w-full rounded-2xl border-slate-200/80 bg-white/80 px-4 py-3 text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-teal-600 focus:ring-teal-600"
            >
        </div>

        <div class="flex items-center justify-between">
            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" name="remember" class="rounded border-slate-300 text-teal-700 focus:ring-teal-600">
                Se souvenir de moi
            </label>
        </div>

        <button
            type="submit"
            class="w-full rounded-2xl bg-slate-950 px-5 py-3.5 text-sm font-semibold text-white shadow-[0_18px_35px_-24px_rgba(15,23,42,.9)] transition duration-300 hover:-translate-y-0.5 hover:bg-teal-700 hover:shadow-[0_22px_40px_-24px_rgba(13,148,136,.9)]"
        >
            Se connecter
        </button>
    </form>

    <div class="mt-7 rounded-[28px] border border-teal-100 bg-gradient-to-br from-white to-teal-50/70 p-5 shadow-[0_18px_45px_-35px_rgba(0,0,0,.45)]">
        <div class="flex items-center justify-between gap-3">
            <div>
                <div class="text-xs uppercase tracking-wider text-teal-700">Assistance My Edu</div>
                <div class="mt-1 text-sm font-semibold text-slate-900">Une aide rapide pour votre acces</div>
                <div class="mt-1 text-xs leading-5 text-slate-600">WhatsApp : 0641612016<br>Email : yassine@myedu.school</div>
            </div>
            <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-white shadow-sm">
                <span class="text-sm font-semibold text-teal-700">ME</span>
            </div>
        </div>
    </div>
</x-guest-layout>
