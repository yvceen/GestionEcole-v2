<x-guest-layout>
    @php
        $schoolContext = app()->bound('currentSchool') ? app('currentSchool') : null;
    @endphp

    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">
            {{ $schoolContext ? $schoolContext->name : 'Login' }}
        </h1>
        <p class="mt-1 text-sm text-slate-600">
            {{ $schoolContext ? 'Connectez-vous pour acceder a l espace de votre ecole.' : 'Connectez-vous pour acceder a votre espace.' }}
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

    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-900">Email</label>
            <input
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                class="w-full rounded-2xl border-slate-200/70 bg-white/70 focus:border-slate-900 focus:ring-slate-900"
            >
        </div>

        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-900">Mot de passe</label>
            <input
                type="password"
                name="password"
                required
                class="w-full rounded-2xl border-slate-200/70 bg-white/70 focus:border-slate-900 focus:ring-slate-900"
            >
        </div>

        <div class="flex items-center justify-between">
            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" name="remember" class="rounded border-slate-300 text-slate-900 focus:ring-slate-900">
                Se souvenir de moi
            </label>
        </div>

        <button
            type="submit"
            class="w-full rounded-2xl bg-black px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-900"
        >
            Login
        </button>
    </form>

    <div class="mt-6 rounded-[28px] border border-black/5 bg-white p-5 shadow-[0_18px_45px_-35px_rgba(0,0,0,.45)]">
        <div class="flex items-center justify-between gap-3">
            <div>
                <div class="text-xs uppercase tracking-wider text-slate-500">Contact</div>
                <div class="mt-1 text-sm font-semibold text-slate-900">Yassine Tourabi</div>
                <div class="mt-1 text-xs text-slate-600">yvceen@gmail.com - 0703977652</div>
            </div>
            <div class="grid h-11 w-11 place-items-center rounded-2xl bg-slate-900/5 font-semibold text-slate-900">
                YT
            </div>
        </div>
    </div>
</x-guest-layout>
