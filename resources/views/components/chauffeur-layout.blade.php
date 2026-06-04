@props([
    'title' => 'Espace chauffeur',
    'subtitle' => 'Pointage transport, circuits et contacts parents.',
])

@php
    $currentSchool = app()->bound('currentSchool')
        ? app('currentSchool')
        : (app()->bound('current_school') ? app('current_school') : null);

    $nav = [
        ['label' => 'Tableau de bord', 'route' => 'chauffeur.dashboard', 'icon' => 'home', 'section' => 'Transport'],
        ['label' => 'Alertes santé', 'route' => 'chauffeur.health.index', 'icon' => 'shield', 'active_routes' => ['chauffeur.health.*'], 'section' => 'Transport'],
    ];
@endphp

<!doctype html>
<html lang="{{ str_replace('_','-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>{{ $title }} | {{ $currentSchool?->name ?? 'MyEdu' }}</title>
    <x-school-favicons />
    @stack('head')
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="app-shell-body min-h-screen overflow-x-hidden m-0 p-0">
    <x-app-shell :links="$nav" navigation-title="Transport">
        <div x-data="portalShell()" data-portal-shell class="ui-scope portal-space space-y-6">
            <div x-cloak x-show="navigating" x-transition.opacity class="portal-loading-overlay" aria-live="polite" aria-busy="true">
                <div class="portal-loading-card">
                    <span class="portal-spinner" aria-hidden="true"></span>
                    <p class="text-sm font-semibold text-slate-900" x-text="loadingLabel"></p>
                </div>
            </div>

            <div x-cloak x-show="navigating" class="portal-loading-bar"></div>

            <x-portal-header
                eyebrow="Transport scolaire"
                :title="$title"
                :subtitle="$subtitle"
                :badges="[
                    auth()->user()?->name ?? 'Chauffeur',
                    $currentSchool?->name ?? 'École active',
                    'Pointage du jour',
                ]"
                summary-title="Mission du jour"
                summary-value="Transport"
                summary-copy="Suivez les Élèves affectés, enregistréz les montées et descentes, puis gardez un journal clair des passages."
                :nav="$nav"
            />

            @if(session('success'))
                <x-ui.alert variant="success">{{ session('success') }}</x-ui.alert>
            @endif

            {{ $slot }}
        </div>
    </x-app-shell>
    @stack('scripts')
</body>
</html>
