<!DOCTYPE html>
<html lang="{{ str_replace('_','-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MyEdu</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        @keyframes floaty { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
        .floaty { animation: floaty 7s ease-in-out infinite; }
    </style>
    <x-school-favicons />
</head>
<body class="min-h-screen overflow-x-hidden bg-[#F6F7FB] text-slate-900 m-0 p-0">
@php
    $schoolContext = app()->bound('currentSchool') ? app('currentSchool') : null;
@endphp

<div class="pointer-events-none fixed inset-0 -z-10">
    <div class="absolute -top-28 -left-28 h-[34rem] w-[34rem] rounded-full bg-white/70 blur-3xl"></div>
    <div class="absolute top-24 -right-28 h-[34rem] w-[34rem] rounded-full bg-white/60 blur-3xl"></div>
    <div class="absolute bottom-0 left-1/4 h-[34rem] w-[34rem] rounded-full bg-white/50 blur-3xl"></div>
</div>

<div class="grid min-h-screen place-items-center px-4 py-10">
    <div class="w-full max-w-md">
        <div class="mb-6 flex items-center justify-between">
            <a href="{{ url('/') }}" class="flex items-center gap-3">
                <x-school-logo size="48" class="h-12 w-12 rounded-full overflow-hidden shadow" />
                <div class="leading-tight">
                    <div class="text-sm font-semibold">{{ $schoolContext?->name ?? config('app.name', 'My-Edu') }}</div>
                    <div class="text-xs text-slate-500">
                        {{ $schoolContext ? 'Espace de connexion de l etablissement' : 'Connexion securisee' }}
                    </div>
                </div>
            </a>
            <a href="{{ url('/') }}" class="text-sm font-semibold text-slate-700 hover:underline">Retour</a>
        </div>

        <div class="floaty rounded-[32px] border border-black/5 bg-white/70 p-6 shadow-[0_28px_70px_-50px_rgba(0,0,0,.55)] backdrop-blur-2xl">
            @isset($slot)
                {{ $slot }}
            @else
                @yield('content')
            @endisset
        </div>

        @if($schoolContext)
            <div class="mt-4 rounded-[24px] border border-slate-200/80 bg-white/80 px-4 py-3 text-sm text-slate-600 shadow-sm">
                <div class="font-semibold text-slate-900">{{ $schoolContext->name }}</div>
                <div class="mt-1 break-all">{{ $schoolContext->appUrl() }}</div>
            </div>
        @endif

        <div class="mt-6 text-center text-xs text-slate-500">
            Besoin d aide ? <span class="font-semibold text-slate-700">yassine@myedu.school</span>
        </div>
    </div>
</div>

</body>
</html>
