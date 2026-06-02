<!DOCTYPE html>
<html lang="{{ str_replace('_','-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Edu</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        @keyframes floaty { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
        @keyframes guestGlow { 0%,100% { opacity: .75; transform: scale(1); } 50% { opacity: 1; transform: scale(1.08); } }
        .floaty { animation: floaty 7s ease-in-out infinite; }
        .guest-glow { animation: guestGlow 9s ease-in-out infinite; }
    </style>
    <x-school-favicons />
</head>
<body class="min-h-screen overflow-x-hidden bg-[#F7F7F2] text-slate-900 m-0 p-0">
@php
    $schoolContext = app()->bound('currentSchool') ? app('currentSchool') : null;
@endphp

<div class="pointer-events-none fixed inset-0 -z-10">
    <div class="guest-glow absolute -top-28 -left-28 h-[34rem] w-[34rem] rounded-full bg-teal-100/70 blur-3xl"></div>
    <div class="guest-glow absolute top-24 -right-28 h-[34rem] w-[34rem] rounded-full bg-sky-100/60 blur-3xl" style="animation-delay: -3s"></div>
    <div class="absolute bottom-0 left-1/4 h-[34rem] w-[34rem] rounded-full bg-white/80 blur-3xl"></div>
</div>

<div class="grid min-h-screen place-items-center px-4 py-10">
    <div class="w-full max-w-md">
        <div class="mb-6 rounded-[28px] border border-white/70 bg-white/70 p-2 shadow-[0_24px_55px_-42px_rgba(15,23,42,.75)] backdrop-blur-2xl">
            <div class="flex items-center justify-between gap-3">
                <a href="{{ url('/') }}" class="group flex min-w-0 items-center gap-3 rounded-3xl px-2 py-1.5 transition hover:bg-white/80">
                    <x-school-logo size="48" class="h-12 w-12 shrink-0 rounded-2xl overflow-hidden shadow-sm ring-1 ring-white/80 transition group-hover:scale-105" />
                    <div class="leading-tight">
                        <div class="truncate text-sm font-semibold text-slate-950">{{ $schoolContext?->name ?? 'My Edu' }}</div>
                        <div class="text-xs text-slate-500">
                            {{ $schoolContext ? 'Espace de connexion de l'Établissement' : 'Expérience éducative sécurisée' }}
                        </div>
                    </div>
                </a>
                <a href="{{ url('/') }}" class="inline-flex shrink-0 items-center gap-2 rounded-2xl bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white shadow-[0_18px_35px_-24px_rgba(15,23,42,.9)] transition hover:-translate-y-0.5 hover:bg-teal-700">
                    <span>Retour</span>
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6" />
                    </svg>
                </a>
            </div>
        </div>

        <div class="floaty rounded-[32px] border border-white/70 bg-white/75 p-6 shadow-[0_30px_80px_-48px_rgba(15,23,42,.65)] backdrop-blur-2xl">
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
            Instagram : <span class="font-semibold text-slate-700">myedu.school</span> | Facebook : <span class="font-semibold text-slate-700">my-edu</span>
        </div>
    </div>
</div>

</body>
</html>
